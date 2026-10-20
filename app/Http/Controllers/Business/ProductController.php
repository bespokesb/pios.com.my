<?php

namespace App\Http\Controllers\Business;

use App\Enums\ProductProposalStatus;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\IdentifierSequence;
use App\Models\Product;
use App\Models\ProductProposal;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $search = $request->input('search');
        $category = $request->input('category', 'all');

        $query = Product::query()->visibleTo($user)->with(['productType', 'vendor']);

        if (filled($search)) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('product_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category !== 'all' && filled($category)) {
            $query->whereHas('productType', function ($q) use ($category): void {
                $q->where('name', 'like', "%{$category}%");
            });
        }

        $products = $query->latest()->get();
        $productTypes = ProductType::all();

        // Also fetch proposals submitted by this BO
        $proposals = ProductProposal::query()->visibleTo($user)->latest()->take(5)->get();

        $bo = $user->businessOwner;
        $productLimit = Product::MAX_PER_BUSINESS_OWNER;
        $productCount = $bo ? $bo->productCount() : Product::query()->visibleTo($user)->count();
        $canAddProduct = $productCount < $productLimit;
        $remainingSlots = max(0, $productLimit - $productCount);

        return view('business.products', compact(
            'products',
            'productTypes',
            'proposals',
            'search',
            'category',
            'productCount',
            'productLimit',
            'canAddProduct',
            'remainingSlots'
        ));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;

        if ($bo?->hasReachedProductLimit() ?? false) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached the maximum limit of '.Product::MAX_PER_BUSINESS_OWNER.' products per Business Owner.',
                ], 422);
            }

            return redirect()->route('business.products.index')
                ->with('error', 'You have reached the maximum limit of '.Product::MAX_PER_BUSINESS_OWNER.' products per Business Owner.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'product_type_id' => ['nullable', 'exists:product_types,id'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $productNo = DB::transaction(function (): string {
            $seq = IdentifierSequence::firstOrCreate(
                ['key' => 'product_no'],
                ['prefix' => 'PRD', 'next_number' => 1, 'padding' => 6]
            );
            $no = $seq->prefix.str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
            $seq->increment('next_number');

            return $no;
        });

        $product = Product::create([
            'business_owner_id' => $bo?->id,
            'product_type_id' => $validated['product_type_id'] ?? null,
            'territory_id' => $bo?->territory_id,
            'product_no' => $productNo,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'selling_price' => $validated['selling_price'],
            'cost' => $validated['cost'] ?? 0,
            'stock_quantity' => $validated['stock_quantity'],
            'status' => ProductStatus::Active,
            'created_by' => $user->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Product added successfully.',
                'product' => $product,
            ]);
        }

        return redirect()->route('business.products.index')
            ->with('success', "Product {$product->name} added successfully.");
    }

    public function propose(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;

        if ($bo?->hasReachedProductLimit() ?? false) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached the maximum limit of '.Product::MAX_PER_BUSINESS_OWNER.' products per Business Owner.',
                ], 422);
            }

            return redirect()->route('business.products.index')
                ->with('error', 'You have reached the maximum limit of '.Product::MAX_PER_BUSINESS_OWNER.' products per Business Owner.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'cost_price' => ['required_without:partner_price', 'nullable', 'numeric', 'min:0'],
            'partner_price' => ['required_without:cost_price', 'nullable', 'numeric', 'min:0'],
            'customer_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'stock_in_hand' => ['nullable', 'integer', 'min:0'],
            'stock_added' => ['nullable', 'integer', 'min:0'],
            'stock_date_added' => ['nullable', 'date'],
            'media_files' => ['nullable', 'array', 'max:4'],
            'media_files.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov', 'max:30720'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:30720'],
        ]);

        $cost = (float) ($validated['cost_price'] ?? $validated['partner_price'] ?? 0);
        $stockInHand = (int) ($validated['stock_in_hand'] ?? 0);
        $stockAdded = (int) ($validated['stock_added'] ?? 0);
        $totalStock = $stockInHand + $stockAdded;
        $stockDate = $validated['stock_date_added'] ?? now()->toDateString();

        // Handle uploaded image media files (up to 4)
        $uploadedPaths = [];
        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('products', 'public');
                    $uploadedPaths[] = $path;
                }
            }
        }

        // Handle optional separate video file upload
        $videoPath = null;
        if ($request->hasFile('video_file') && $request->file('video_file')->isValid()) {
            $videoPath = $request->file('video_file')->store('products/videos', 'public');
        }

        $proposalData = [
            'business_owner_id' => $bo?->id,
            'name' => $validated['name'],
            'area' => $validated['area'] ?? ($bo?->bandar.', '.$bo?->negeri),
            'partner_price' => $cost,
            'customer_price' => $validated['customer_price'],
            'description' => $validated['description'] ?? null,
            'image_video_path' => $uploadedPaths[0] ?? $videoPath,
            'approval_status' => ProductProposalStatus::Pending,
        ];

        if (Schema::hasColumn('product_proposals', 'cost_price')) {
            $proposalData['cost_price'] = $cost;
        }
        if (Schema::hasColumn('product_proposals', 'stock_in_hand')) {
            $proposalData['stock_in_hand'] = $stockInHand;
        }
        if (Schema::hasColumn('product_proposals', 'stock_added')) {
            $proposalData['stock_added'] = $stockAdded;
        }
        if (Schema::hasColumn('product_proposals', 'stock_date_added')) {
            $proposalData['stock_date_added'] = $stockDate;
        }
        if (Schema::hasColumn('product_proposals', 'media_assets')) {
            $proposalData['media_assets'] = $uploadedPaths;
        }
        if ($videoPath && Schema::hasColumn('product_proposals', 'video_path')) {
            $proposalData['video_path'] = $videoPath;
        }

        $proposal = ProductProposal::create($proposalData);

        // Also create the active product record so the Business Owner sees it immediately in their catalog
        $productNo = DB::transaction(function (): string {
            $seq = IdentifierSequence::firstOrCreate(
                ['key' => 'product_no'],
                ['prefix' => 'PRD', 'next_number' => 1, 'padding' => 6]
            );
            $no = $seq->prefix.str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
            $seq->increment('next_number');

            return $no;
        });

        $productData = [
            'business_owner_id' => $bo?->id,
            'territory_id' => $bo?->territory_id,
            'product_no' => $productNo,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'selling_price' => $validated['customer_price'],
            'cost' => $cost,
            'stock_quantity' => $totalStock,
            'coverage_area' => $validated['area'] ?? ($bo?->bandar.', '.$bo?->negeri),
            'media_assets' => $uploadedPaths,
            'status' => ProductStatus::Active,
            'created_by' => $user->id,
        ];

        if ($videoPath && Schema::hasColumn('products', 'video_path')) {
            $productData['video_path'] = $videoPath;
        }

        $product = Product::create($productData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Product submitted successfully.',
                'proposal' => $proposal,
                'product' => $product,
            ]);
        }

        return redirect()->route('business.products.index')
            ->with('success', "Product '{$product->name}' added successfully.");
    }

    public function update(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;

        abort_unless($user->isBusinessOwner() && $product->business_owner_id === $bo?->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'customer_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'stock_added' => ['nullable', 'integer', 'min:0'],
            'coverage_area' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'media_files' => ['nullable', 'array', 'max:4'],
            'media_files.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov', 'max:30720'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:30720'],
            'remove_video' => ['nullable', 'boolean'],
        ]);

        $product->name = $validated['name'];
        if (array_key_exists('description', $validated)) {
            $product->description = $validated['description'];
        }

        $sellingPrice = $validated['selling_price'] ?? $validated['customer_price'] ?? null;
        if ($sellingPrice !== null) {
            $product->selling_price = (float) $sellingPrice;
        }

        $cost = $validated['cost'] ?? $validated['cost_price'] ?? null;
        if ($cost !== null) {
            $product->cost = (float) $cost;
        }

        // Calculate and update stock
        $currentStock = isset($validated['stock_quantity']) ? (int) $validated['stock_quantity'] : (int) $product->stock_quantity;
        $addedStock = isset($validated['stock_added']) ? (int) $validated['stock_added'] : 0;
        $product->stock_quantity = max(0, $currentStock + $addedStock);

        $coverageArea = $validated['coverage_area'] ?? $validated['area'] ?? null;
        if ($coverageArea !== null) {
            $product->coverage_area = $coverageArea;
        }

        if (! empty($validated['status'])) {
            $product->status = $validated['status'] === 'active' ? ProductStatus::Active : ProductStatus::Inactive;
        }

        // Handle new image media files
        if ($request->hasFile('media_files')) {
            $uploadedPaths = [];
            foreach ($request->file('media_files') as $file) {
                if ($file->isValid()) {
                    $uploadedPaths[] = $file->store('products', 'public');
                }
            }
            if (! empty($uploadedPaths)) {
                $product->media_assets = $uploadedPaths;
            }
        }

        // Handle video file upload / removal
        if ($request->hasFile('video_file') && $request->file('video_file')->isValid()) {
            $product->video_path = $request->file('video_file')->store('products/videos', 'public');
        } elseif ($request->boolean('remove_video')) {
            $product->video_path = null;
        }

        $product->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Product '{$product->name}' updated successfully.",
                'product' => $product->fresh(),
            ]);
        }

        return redirect()->route('business.products.index')
            ->with('success', "Product '{$product->name}' updated successfully.");
    }

    public function destroy(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;

        abort_unless($user->isBusinessOwner() && $product->business_owner_id === $bo?->id, 403);

        $productName = $product->name;

        DB::transaction(function () use ($product): void {
            if (method_exists($product, 'promotions')) {
                $product->promotions()->delete();
            }
            $product->delete();
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Product '{$productName}' deleted successfully. One catalog slot is now available.",
            ]);
        }

        return redirect()->route('business.products.index')
            ->with('success', "Product '{$productName}' deleted successfully. One catalog slot is now available.");
    }
}
