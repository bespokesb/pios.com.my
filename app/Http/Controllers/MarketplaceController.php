<?php

namespace App\Http\Controllers;

use App\Enums\CustomerCategory;
use App\Enums\ProductStatus;
use App\Enums\RecordStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductType;
use App\Services\IdentifierGenerator;
use App\Services\ManualOrderService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    /**
     * Browse active products with search, category filtering, and sorting.
     */
    public function index(Request $request): View
    {
        $currentCustomer = $this->resolveCurrentCustomer($request);

        $query = Product::query()
            ->with(['productType', 'businessOwner'])
            ->where('status', ProductStatus::Active)
            ->whereNotNull('business_owner_id');

        // Scope to creator (business_owner_id) if customer is logged in / identified
        if (! empty($currentCustomer['business_owner_id'])) {
            $query->where('business_owner_id', $currentCustomer['business_owner_id']);
        } elseif ($boId = $request->input('bo', $request->input('business_owner_id'))) {
            $query->where('business_owner_id', $boId);
        }

        // Search by product name or description
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category (product_type_id or code)
        if ($category = $request->input('category')) {
            $query->whereHas('productType', function ($q) use ($category): void {
                $q->where('code', $category)->orWhere('id', $category);
            });
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'price_low' => $query->orderBy('selling_price', 'asc'),
            'price_high' => $query->orderBy('selling_price', 'desc'),
            'name' => $query->orderBy('name', 'asc'),
            default => $query->latest('id'),
        };

        $products = $query->paginate(12)->withQueryString();
        $categories = ProductType::query()->where('status', 'active')->orderBy('name')->get();
        $cart = session()->get('market_cart', []);
        $cartCount = array_sum(array_column($cart, 'quantity'));

        return view('marketplace.index', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $category,
            'searchQuery' => $search,
            'currentSort' => $sort,
            'cart' => $cart,
            'cartCount' => $cartCount,
            'customer' => $currentCustomer,
        ]);
    }

    /**
     * Add an item to session cart.
     */
    public function addToCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        abort_unless($product->isPubliclyOrderable(), 404);

        $cart = session()->get('market_cart', []);
        $productId = $product->id;
        $qty = (int) $validated['quantity'];

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $qty;
        } else {
            $cart[$productId] = [
                'id' => $product->id,
                'product_no' => $product->product_no,
                'name' => $product->name,
                'selling_price' => (float) $product->selling_price,
                'image_url' => $product->firstShareableImageUrl(),
                'quantity' => $qty,
                'seller_name' => $product->businessOwner?->applicant_name ?? 'PIOS Seller',
            ];
        }

        session()->put('market_cart', $cart);

        return redirect()->back()->with('cart_success', __('Added :name to cart.', ['name' => $product->name]));
    }

    /**
     * Update quantity or remove an item from session cart.
     */
    public function updateCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'action' => ['required', 'in:update,remove'],
            'quantity' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        $cart = session()->get('market_cart', []);
        $productId = $validated['product_id'];

        if ($validated['action'] === 'remove' || ($validated['action'] === 'update' && (int) ($validated['quantity'] ?? 0) <= 0)) {
            unset($cart[$productId]);
        } elseif (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = (int) $validated['quantity'];
        }

        session()->put('market_cart', $cart);

        return redirect()->back()->with('cart_success', __('Cart updated.'));
    }

    /**
     * Render the Checkout page.
     */
    public function checkout(Request $request): View|RedirectResponse
    {
        $cart = session()->get('market_cart', []);
        if (empty($cart)) {
            return redirect()->route('marketplace.index')->with('warning', __('Your cart is empty.'));
        }

        $customer = $this->resolveCurrentCustomer($request);

        $items = [];
        $grandTotal = 0;

        foreach ($cart as $id => $item) {
            $product = Product::query()->with('businessOwner')->find($id);
            if ($product && $product->isPubliclyOrderable()) {
                $subtotal = round($item['quantity'] * (float) $product->selling_price, 2);
                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => (float) $product->selling_price,
                    'subtotal' => $subtotal,
                ];
                $grandTotal += $subtotal;
            }
        }

        return view('marketplace.checkout', [
            'items' => $items,
            'grandTotal' => $grandTotal,
            'customer' => $customer,
        ]);
    }

    /**
     * Process order submission for items in cart.
     */
    public function processCheckout(Request $request, ManualOrderService $orders): View|RedirectResponse
    {
        $cart = session()->get('market_cart', []);
        if (empty($cart)) {
            return redirect()->route('marketplace.index')->with('warning', __('Your cart is empty.'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $placedOrders = [];

        foreach ($cart as $id => $item) {
            $product = Product::query()->with('businessOwner')->find($id);
            if ($product && $product->isPubliclyOrderable()) {
                $buyerData = [
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                    'quantity' => $item['quantity'],
                    'address' => $validated['address'],
                    'note' => $validated['note'] ?? null,
                ];

                $order = $orders->placePublicOrder($product, $buyerData);
                $placedOrders[] = [
                    'order' => $order,
                    'product' => $product,
                    'whatsapp' => Phone::toWhatsApp($product->businessOwner?->hp_no),
                ];
            }
        }

        // Store customer details in session for auto-fill and recognition
        session()->put('market_customer_name', $validated['name']);
        session()->put('market_customer_phone', $validated['phone']);
        session()->put('market_customer_address', $validated['address']);

        // Empty cart after checkout
        session()->forget('market_cart');

        return view('marketplace.confirmation', [
            'placedOrders' => $placedOrders,
            'buyer' => $validated,
        ]);
    }

    /**
     * Customer login/identification by Phone, IC, or Email.
     */
    public function loginCustomer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $input = trim($validated['identifier']);
        $customer = null;

        // Try phone match
        $digits = Phone::digits($input);
        if ($digits !== '') {
            $customer = Customer::query()
                ->get()
                ->first(fn ($c): bool => Phone::digits((string) $c->hp_no) === $digits
                    || Phone::toWhatsApp((string) $c->hp_no) === Phone::toWhatsApp($input));
        }

        // Try KP (IC) or Email match if phone match failed
        if (! $customer) {
            $customer = Customer::query()
                ->where('kp_no', $input)
                ->orWhere('email', $input)
                ->first();
        }

        if ($customer) {
            $customerData = [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->hp_no,
                'email' => $customer->email,
                'kp_no' => $customer->kp_no,
                'address' => $customer->address,
                'is_vip' => (bool) $customer->is_vip,
            ];

            session()->put('market_customer', $customerData);
            session()->put('market_customer_id', $customer->id);
            session()->put('market_customer_name', $customer->name);
            session()->put('market_customer_phone', $customer->hp_no);
            session()->put('market_customer_email', $customer->email);
            session()->put('market_customer_kp_no', $customer->kp_no);
            session()->put('market_customer_address', $customer->address);

            $targetUrl = $request->input('redirect_to') ?: redirect()->back()->getTargetUrl();

            return redirect($targetUrl)->with('customer_status', __('Welcome back, :name!', ['name' => $customer->name]));
        }

        $targetUrl = $request->input('redirect_to') ?: redirect()->back()->getTargetUrl();

        return redirect($targetUrl)
            ->withInput()
            ->with('customer_login_error', __('Pelanggan tidak dijumpai. Hanya Business Owner sahaja yang boleh mendaftarkan pelanggan baharu. Sila hubungi peniaga untuk pendaftaran.'))
            ->with('show_login_modal', true);
    }

    /**
     * Display the Customer Registration page.
     */
    public function showRegisterForm(Request $request): View
    {
        $customer = $this->resolveCurrentCustomer($request);

        return view('marketplace.register', [
            'customer' => $customer,
        ]);
    }

    /**
     * Process Customer Registration.
     */
    public function registerCustomer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'kp_no' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $digits = Phone::digits($validated['phone']);

        $customer = Customer::query()
            ->get()
            ->first(fn ($c): bool => Phone::digits((string) $c->hp_no) === $digits
                || (filled($validated['kp_no'] ?? null) && $c->kp_no === $validated['kp_no'])
                || (filled($validated['email'] ?? null) && strtolower((string) $c->email) === strtolower((string) $validated['email'])));

        if (! $customer) {
            $bo = BusinessOwner::query()->first();
            if (! $bo) {
                $partner = Partner::query()->firstOrCreate(
                    ['partner_no' => 'P000001'],
                    ['applicant_name' => 'PIOS System Partner']
                );
                $bo = BusinessOwner::query()->firstOrCreate(
                    ['business_owner_no' => 'BO000001'],
                    ['partner_id' => $partner->id, 'applicant_name' => 'PIOS System Merchant']
                );
            }

            $customer = new Customer;
            $customer->business_owner_id = $bo->id;
            $customer->partner_id = $bo->partner_id;
            $customer->customer_no = app(IdentifierGenerator::class)->generate('customer', Customer::class, 'customer_no');
            $customer->category = CustomerCategory::Lead;
            $customer->status = RecordStatus::Active;
        }

        $customer->name = $validated['name'];
        $customer->hp_no = $validated['phone'];
        if (filled($validated['kp_no'] ?? null)) {
            $customer->kp_no = $validated['kp_no'];
        }
        if (filled($validated['email'] ?? null)) {
            $customer->email = $validated['email'];
        }
        if (filled($validated['address'] ?? null)) {
            $customer->address = $validated['address'];
        }

        $customer->save();

        $customerData = [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->hp_no,
            'email' => $customer->email,
            'kp_no' => $customer->kp_no,
            'address' => $customer->address,
            'is_vip' => (bool) $customer->is_vip,
        ];

        session()->put('market_customer', $customerData);
        session()->put('market_customer_id', $customer->id);
        session()->put('market_customer_name', $customer->name);
        session()->put('market_customer_phone', $customer->hp_no);
        session()->put('market_customer_email', $customer->email);
        session()->put('market_customer_kp_no', $customer->kp_no);
        session()->put('market_customer_address', $customer->address);

        $redirectTo = session()->get('market_cart') ? route('marketplace.checkout') : route('marketplace.index');

        return redirect($redirectTo)->with('customer_status', __('Registration successful! Welcome, :name.', ['name' => $customer->name]));
    }

    /**
     * Clear customer identification session.
     */
    public function logoutCustomer(Request $request): RedirectResponse
    {
        session()->forget([
            'market_customer',
            'market_customer_id',
            'market_customer_name',
            'market_customer_phone',
            'market_customer_email',
            'market_customer_kp_no',
            'market_customer_address',
        ]);
        session()->save();

        $redirectTo = $request->input('redirect_to') ?: redirect()->back()->getTargetUrl();

        return redirect($redirectTo)
            ->with('customer_status', __('Logged out successfully.'))
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    /**
     * Resolve active customer data from session or DB.
     *
     * @return array{id: ?int, name: ?string, phone: ?string, email: ?string, kp_no: ?string, address: ?string, is_vip: bool}
     */
    private function resolveCurrentCustomer(Request $request): array
    {
        $id = session()->get('market_customer_id');
        $customerModel = $id ? Customer::query()->find($id) : null;

        $sessionCustomer = session()->get('market_customer', []);

        return [
            'id' => $customerModel?->id ?? $sessionCustomer['id'] ?? null,
            'business_owner_id' => $customerModel?->business_owner_id ?? $sessionCustomer['business_owner_id'] ?? null,
            'name' => $customerModel?->name ?? $sessionCustomer['name'] ?? session()->get('market_customer_name'),
            'phone' => $customerModel?->hp_no ?? $sessionCustomer['phone'] ?? session()->get('market_customer_phone'),
            'email' => $customerModel?->email ?? $sessionCustomer['email'] ?? session()->get('market_customer_email'),
            'kp_no' => $customerModel?->kp_no ?? $sessionCustomer['kp_no'] ?? session()->get('market_customer_kp_no'),
            'address' => $customerModel?->address ?? $sessionCustomer['address'] ?? session()->get('market_customer_address'),
            'is_vip' => (bool) ($customerModel?->is_vip ?? $sessionCustomer['is_vip'] ?? false),
        ];
    }
}
