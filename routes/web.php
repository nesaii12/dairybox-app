<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public Landing Route
Route::get('/', function () {
    $products = \App\Models\Product::with('category')->withCount('ratings')->withAvg('ratings', 'rating')->active()->take(4)->get();
    $allActiveProducts = \App\Models\Product::active()->get();
    $productVariantsMap = [];
    foreach ($allActiveProducts as $p) {
        $key = strtolower(trim($p->product_name));
        $expired = $p->expiration_date && \Carbon\Carbon::parse($p->expiration_date)->startOfDay()->lt(now()->startOfDay());
        $productVariantsMap[$key][] = [
            'id' => $p->id,
            'milk_type' => $p->milk_type,
            'size' => $p->size,
            'available' => !$expired && $p->stock > 0,
            'url' => route('customer.product', $p->id),
        ];
    }
    return view('welcome', compact('products', 'productVariantsMap'));
})->name('home');

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/register/email-otp/send', [AuthController::class, 'sendEmailOtp'])->name('register.email.sendOtp')->middleware('throttle:5,1');
    Route::post('/register/email-otp/verify', [AuthController::class, 'verifyEmailOtp'])->name('register.email.verifyOtp')->middleware('throttle:10,1');
    Route::post('/register/phone-otp/send', [AuthController::class, 'sendPhoneOtp'])->name('register.phone.sendOtp')->middleware('throttle:5,1');
    Route::post('/register/phone-otp/verify', [AuthController::class, 'verifyPhoneOtp'])->name('register.phone.verifyOtp')->middleware('throttle:10,1');

    // Google OAuth Routes
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

    // Password Reset Routes
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
});

// OTP Email Verification Routes
Route::get('/verify-otp', [AuthController::class, 'showOtpVerification'])->name('otp.verify');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('otp.submit')->middleware('throttle:10,1');
Route::post('/verify-otp/resend', [AuthController::class, 'resendOtp'])->name('otp.resend')->middleware('throttle:5,1');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Admin Routes (Role: admin)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    
    // Products
    Route::resource('products', \App\Http\Controllers\Admin\ProductController::class)->except('edit');
    
    // Categories
    Route::get('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
    Route::delete('/categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('categories.destroy');
    
    // Inventory
    Route::get('/inventory', [\App\Http\Controllers\Admin\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/adjust', [\App\Http\Controllers\Admin\InventoryController::class, 'adjust'])->name('inventory.adjust');
    
    // Orders
    Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/update-status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('/orders/{order}/assign-rider', [\App\Http\Controllers\Admin\OrderController::class, 'assignRider'])->name('orders.assignRider');

    // Walk-in Sales
    Route::get('/walk-in-sales/create', [\App\Http\Controllers\Admin\WalkInSaleController::class, 'create'])->name('walkInSales.create');
    Route::post('/walk-in-sales', [\App\Http\Controllers\Admin\WalkInSaleController::class, 'store'])->name('walkInSales.store');
    
    // Customers
    Route::get('/customers', [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/restrict', [\App\Http\Controllers\Admin\CustomerController::class, 'restrict'])->name('customers.restrict');
    Route::post('/customers/{customer}/unrestrict', [\App\Http\Controllers\Admin\CustomerController::class, 'unrestrict'])->name('customers.unrestrict');
    Route::delete('/customers/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'destroy'])->name('customers.destroy');
    
    // Delivery Monitoring & Incident Handling
    Route::get('/deliveries', [\App\Http\Controllers\Admin\DeliveryController::class, 'index'])->name('deliveries.index');
    Route::post('/deliveries/{delivery}/verify-restrict', [\App\Http\Controllers\Admin\DeliveryController::class, 'verifyAndRestrict'])->name('deliveries.verifyRestrict');
    Route::post('/deliveries/{delivery}/cancel', [\App\Http\Controllers\Admin\DeliveryController::class, 'cancelDelivery'])->name('deliveries.cancel');

    // Riders
    Route::resource('riders', \App\Http\Controllers\Admin\RiderController::class)->except('edit');

    // Rider Cash Remittances & Daily Cash Ledger
    Route::get('/remittances', [\App\Http\Controllers\Admin\RiderRemittanceController::class, 'index'])->name('remittances.index');
    Route::get('/remittances/export/excel', [\App\Http\Controllers\Admin\RiderRemittanceController::class, 'exportExcel'])->name('remittances.export.excel');
    Route::get('/remittances/export/pdf', [\App\Http\Controllers\Admin\RiderRemittanceController::class, 'exportPdf'])->name('remittances.export.pdf');
    Route::get('/remittances/rider/{rider}/orders', [\App\Http\Controllers\Admin\RiderRemittanceController::class, 'getRiderOrders'])->name('remittances.riderOrders');
    Route::post('/remittances', [\App\Http\Controllers\Admin\RiderRemittanceController::class, 'store'])->name('remittances.store');
    Route::get('/remittances/{remittance}', [\App\Http\Controllers\Admin\RiderRemittanceController::class, 'show'])->name('remittances.show');
    Route::post('/remittances/{remittance}/verify', [\App\Http\Controllers\Admin\RiderRemittanceController::class, 'verify'])->name('remittances.verify');
    Route::post('/remittances/{remittance}/reject', [\App\Http\Controllers\Admin\RiderRemittanceController::class, 'reject'])->name('remittances.reject');
    
    // Reports
    Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales/excel', [\App\Http\Controllers\Admin\ReportController::class, 'salesExcel'])->name('reports.salesExcel');
    Route::get('/reports/sales/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'salesPdf'])->name('reports.salesPdf');
    Route::get('/reports/inventory/excel', [\App\Http\Controllers\Admin\ReportController::class, 'inventoryExcel'])->name('reports.inventoryExcel');
    Route::get('/reports/inventory/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'inventoryPdf'])->name('reports.inventoryPdf');
    
    // Forecasting
    Route::get('/forecast', [\App\Http\Controllers\Admin\ForecastController::class, 'index'])->name('forecast.index');
    Route::post('/forecast/generate', [\App\Http\Controllers\Admin\ForecastController::class, 'generate'])->name('forecast.generate');
    Route::post('/forecast/generate-all', [\App\Http\Controllers\Admin\ForecastController::class, 'generateAll'])->name('forecast.generateAll');

    // Notifications
    Route::post('/notifications/read-all', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('notifications.read');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [\App\Http\Controllers\Admin\SettingController::class, 'updateProfile'])->name('settings.updateProfile');
    Route::put('/settings/password', [\App\Http\Controllers\Admin\SettingController::class, 'updatePassword'])->name('settings.updatePassword');
    Route::put('/settings/store', [\App\Http\Controllers\Admin\SettingController::class, 'updateStore'])->name('settings.updateStore');
});

// Public Customer Catalog Routes
Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/products', [\App\Http\Controllers\Customer\StoreController::class, 'index'])->name('catalog');
    Route::get('/products/{product}', [\App\Http\Controllers\Customer\StoreController::class, 'show'])->name('product');
});

// Customer Routes (Role: customer)
Route::middleware(['auth', 'role:customer', 'otp.verified'])->prefix('customer')->name('customer.')->group(function () {
    // Cart
    Route::get('/cart', [\App\Http\Controllers\Customer\CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [\App\Http\Controllers\Customer\CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update/{item}', [\App\Http\Controllers\Customer\CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove/{item}', [\App\Http\Controllers\Customer\CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/batch/new', [\App\Http\Controllers\Customer\CartController::class, 'startNewBatch'])->name('cart.newBatch');
    Route::post('/cart/batch/{cart}/remove', [\App\Http\Controllers\Customer\CartController::class, 'removeBatch'])->name('cart.removeBatch');
    
    // Checkout (requires check.restriction to ensure restricted users can't place orders)
    Route::middleware('check.restriction')->group(function () {
        Route::get('/checkout', [\App\Http\Controllers\Customer\CartController::class, 'checkout'])->name('checkout.index');
        Route::post('/checkout/place', [\App\Http\Controllers\Customer\CartController::class, 'placeOrder'])
            ->name('checkout.placeOrder')
            ->middleware('throttle:10,1');
    });
    
    // Orders
    Route::get('/orders', [\App\Http\Controllers\Customer\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\Customer\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [\App\Http\Controllers\Customer\OrderController::class, 'cancel'])->name('orders.cancel');
    
    // Ratings & Reviews
    Route::post('/products/{product}/rate', [\App\Http\Controllers\Customer\RatingController::class, 'store'])
        ->name('ratings.store')
        ->middleware('throttle:10,1');
    
    // Notifications
    Route::post('/notifications/read-all', [\App\Http\Controllers\Customer\NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Customer\NotificationController::class, 'markAsRead'])->name('notifications.read');

    // Account Settings
    Route::get('/settings', [\App\Http\Controllers\Customer\SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [\App\Http\Controllers\Customer\SettingController::class, 'updateProfile'])->name('settings.updateProfile');
    Route::put('/settings/password', [\App\Http\Controllers\Customer\SettingController::class, 'updatePassword'])->name('settings.updatePassword');

    // Payments (Xendit Return & Retry)
    Route::get('/orders/{order}/payment/success', [\App\Http\Controllers\Customer\PaymentController::class, 'success'])->name('orders.payment.success');
    Route::get('/orders/{order}/payment/failure', [\App\Http\Controllers\Customer\PaymentController::class, 'failure'])->name('orders.payment.failure');
    Route::get('/orders/{order}/payment/retry', [\App\Http\Controllers\Customer\PaymentController::class, 'retry'])->name('orders.payment.retry');
});

// Xendit Webhook (CSRF excluded in bootstrap/app.php)
Route::post('/api/webhooks/xendit', [\App\Http\Controllers\Webhook\XenditWebhookController::class, 'handle'])->name('webhooks.xendit');

// Global Authenticated Notification Click & Redirect Route (Applies to Admin, Customer, and Rider)
Route::middleware('auth')->get('/notifications/{notification}/go', [\App\Http\Controllers\NotificationClickController::class, 'handleClick'])
    ->name('notifications.readAndRedirect');

// Rider Routes (Role: rider)
Route::middleware(['auth', 'role:rider'])->prefix('rider')->name('rider.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Rider\RiderController::class, 'index'])->name('dashboard');
    Route::get('/api/live-check', [\App\Http\Controllers\Rider\RiderController::class, 'liveCheck'])->name('api.liveCheck');
    Route::get('/orders/{order}', [\App\Http\Controllers\Rider\RiderController::class, 'showOrder'])->name('orders.show');
    Route::post('/deliveries/{delivery}/mark-arrived', [\App\Http\Controllers\Rider\RiderController::class, 'markArrived'])->name('deliveries.markArrived');
    Route::post('/deliveries/{delivery}/update-status', [\App\Http\Controllers\Rider\RiderController::class, 'updateStatus'])->name('deliveries.updateStatus');
    Route::get('/history', [\App\Http\Controllers\Rider\RiderController::class, 'history'])->name('history');

    // Notifications
    Route::post('/notifications/read-all', [\App\Http\Controllers\Customer\NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Customer\NotificationController::class, 'markAsRead'])->name('notifications.read');
});
