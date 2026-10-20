<?php

use App\Enums\ProductStatus;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Business\CustomerController;
use App\Http\Controllers\Business\DashboardController;
use App\Http\Controllers\Business\FeeController;
use App\Http\Controllers\Business\ProductController;
use App\Http\Controllers\Business\ReportController;
use App\Http\Controllers\Business\TransactionController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\OrderDocumentController;
use App\Http\Controllers\PublicProductController;
use App\Http\Middleware\EnsureBusinessOwnerPortalAccess;
use App\Http\Middleware\EnsureMarketplaceEnabled;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

// Public Landing Page with Real Catalog Products from Database
Route::get('/', function () {
    $products = Product::query()
        ->where('status', ProductStatus::Active)
        ->with('productType')
        ->latest()
        ->take(12)
        ->get();

    return view('welcome', compact('products'));
})->name('home');

// Authentication Routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Business Owner Custom Responsive Portal (All under /business/*)
Route::middleware(['auth', EnsureBusinessOwnerPortalAccess::class])->group(function (): void {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('business.dashboard');

    // Customers
    Route::get('/business/customers', [CustomerController::class, 'index'])->name('business.customers.index');
    Route::get('/business/customers-check-phone', [CustomerController::class, 'checkPhone'])->name('business.customers.check-phone');
    Route::get('/business/customers/check-phone', [CustomerController::class, 'checkPhone']);
    Route::post('/business/customers', [CustomerController::class, 'store'])->name('business.customers.store');
    Route::put('/business/customers/{customer}', [CustomerController::class, 'update'])->name('business.customers.update');
    Route::delete('/business/customers/{customer}', [CustomerController::class, 'destroy'])->name('business.customers.destroy');
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/check-phone', [CustomerController::class, 'checkPhone']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);

    // Products
    Route::get('/business/products', [ProductController::class, 'index'])->name('business.products.index');
    Route::post('/business/products', [ProductController::class, 'store'])->name('business.products.store');
    Route::post('/business/products/propose', [ProductController::class, 'propose'])->name('business.products.propose');
    Route::put('/business/products/{product}', [ProductController::class, 'update'])->name('business.products.update');
    Route::delete('/business/products/{product}', [ProductController::class, 'destroy'])->name('business.products.destroy');
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::post('/products/propose', [ProductController::class, 'propose']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Transactions / Orders
    Route::get('/business/transactions', [TransactionController::class, 'index'])->name('business.transactions.index');
    Route::post('/business/transactions/{order}/status', [TransactionController::class, 'updateStatus'])->name('business.transactions.status');
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions/{order}/status', [TransactionController::class, 'updateStatus']);

    // Fees
    Route::get('/business/fees', [FeeController::class, 'index'])->name('business.fees.index');
    Route::post('/business/fees/{fee}/pay', [FeeController::class, 'pay'])->name('business.fees.pay');
    Route::get('/fees', [FeeController::class, 'index']);
    Route::post('/fees/{fee}/pay', [FeeController::class, 'pay']);

    // Reports
    Route::get('/business/reports', [ReportController::class, 'index'])->name('business.reports.index');
    Route::get('/reports', [ReportController::class, 'index']);
});

// Public Customer Marketplace
Route::middleware([EnsureMarketplaceEnabled::class])->group(function (): void {
    Route::get('/market', [MarketplaceController::class, 'index'])->name('marketplace.index');
    Route::post('/market/cart/add', [MarketplaceController::class, 'addToCart'])->name('marketplace.cart.add');
    Route::post('/market/cart/update', [MarketplaceController::class, 'updateCart'])->name('marketplace.cart.update');
    Route::get('/market/checkout', [MarketplaceController::class, 'checkout'])->name('marketplace.checkout');
    Route::post('/market/checkout', [MarketplaceController::class, 'processCheckout'])->name('marketplace.checkout.process');
    Route::get('/market/register', [MarketplaceController::class, 'showRegisterForm'])->name('marketplace.register');
    Route::post('/market/register', [MarketplaceController::class, 'registerCustomer'])->name('marketplace.register.process');
});

// Customer Authentication (Always active for product pages & marketplace)
Route::post('/market/login', [MarketplaceController::class, 'loginCustomer'])->name('marketplace.login');
Route::post('/market/logout', [MarketplaceController::class, 'logoutCustomer'])->name('marketplace.logout');

// Public, mobile-first product page shared via WhatsApp marketing blasts.
Route::get('/p/{product:product_no}', [PublicProductController::class, 'show'])
    ->name('public.product.show');
Route::post('/p/{product:product_no}/order', [PublicProductController::class, 'order'])
    ->middleware('throttle:8,1')
    ->name('public.product.order');

// Printable order documents (invoice / receipt).
Route::middleware('auth')->group(function (): void {
    Route::get('/orders/{order}/invoice', [OrderDocumentController::class, 'invoice'])->name('order.invoice');
    Route::get('/orders/{order}/receipt', [OrderDocumentController::class, 'receipt'])->name('order.receipt');
});

Route::get('/locale/{locale}', function (Request $request, string $locale) {
    abort_unless(array_key_exists($locale, config('app.supported_locales', [])), 404);

    $request->session()->put('locale', $locale);
    App::setLocale($locale);

    return redirect()->back(fallback: route('filament.business.auth.login'));
})->name('locale.switch');
