<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public Landing Route
Route::get('/', function () {
    $products = \App\Models\Product::with('category')->active()->take(4)->get();
    return view('welcome', compact('products'));
})->name('home');

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Admin Routes (Role: admin)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    
    // Products
    Route::resource('products', \App\Http\Controllers\Admin\ProductController::class)->except('edit');
    
    // Categories
    Route::get('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
    
    // Inventory
    Route::get('/inventory', [\App\Http\Controllers\Admin\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/adjust', [\App\Http\Controllers\Admin\InventoryController::class, 'adjust'])->name('inventory.adjust');
    
    // Orders
    Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/update-status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('/orders/{order}/assign-rider', [\App\Http\Controllers\Admin\OrderController::class, 'assignRider'])->name('orders.assignRider');
    
    // Customers
    Route::get('/customers', [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/restrict', [\App\Http\Controllers\Admin\CustomerController::class, 'restrict'])->name('customers.restrict');
    
    // Riders
    Route::resource('riders', \App\Http\Controllers\Admin\RiderController::class)->except('edit');
    
    // Reports
    Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales/excel', [\App\Http\Controllers\Admin\ReportController::class, 'salesExcel'])->name('reports.salesExcel');
    Route::get('/reports/sales/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'salesPdf'])->name('reports.salesPdf');
    Route::get('/reports/inventory/excel', [\App\Http\Controllers\Admin\ReportController::class, 'inventoryExcel'])->name('reports.inventoryExcel');
    Route::get('/reports/inventory/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'inventoryPdf'])->name('reports.inventoryPdf');
    
    // Forecasting
    Route::get('/forecast', [\App\Http\Controllers\Admin\ForecastController::class, 'index'])->name('forecast.index');
    Route::post('/forecast/generate', [\App\Http\Controllers\Admin\ForecastController::class, 'generate'])->name('forecast.generate');
});

// Public Customer Catalog Routes
Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/products', [\App\Http\Controllers\Customer\StoreController::class, 'index'])->name('catalog');
    Route::get('/products/{product}', [\App\Http\Controllers\Customer\StoreController::class, 'show'])->name('product');
});

// Customer Routes (Role: customer)
Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    // Cart
    Route::get('/cart', [\App\Http\Controllers\Customer\CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [\App\Http\Controllers\Customer\CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update/{item}', [\App\Http\Controllers\Customer\CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove/{item}', [\App\Http\Controllers\Customer\CartController::class, 'remove'])->name('cart.remove');
    
    // Checkout (requires check.restriction to ensure restricted users can't place orders)
    Route::middleware('check.restriction')->group(function () {
        Route::get('/checkout', [\App\Http\Controllers\Customer\CartController::class, 'checkout'])->name('checkout.index');
        Route::post('/checkout/place', [\App\Http\Controllers\Customer\CartController::class, 'placeOrder'])->name('checkout.placeOrder');
    });
    
    // Orders
    Route::get('/orders', [\App\Http\Controllers\Customer\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\Customer\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [\App\Http\Controllers\Customer\OrderController::class, 'cancel'])->name('orders.cancel');
    
    // Ratings & Reviews
    Route::post('/products/{product}/rate', [\App\Http\Controllers\Customer\RatingController::class, 'store'])->name('ratings.store');
    
    // Notifications
    Route::post('/notifications/read-all', [\App\Http\Controllers\Customer\NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Customer\NotificationController::class, 'markAsRead'])->name('notifications.read');
});

// Rider Routes (Role: rider)
Route::middleware(['auth', 'role:rider'])->prefix('rider')->name('rider.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Rider\RiderController::class, 'index'])->name('dashboard');
    Route::post('/deliveries/{delivery}/update-status', [\App\Http\Controllers\Rider\RiderController::class, 'updateStatus'])->name('deliveries.updateStatus');
    Route::get('/history', [\App\Http\Controllers\Rider\RiderController::class, 'history'])->name('history');
});
