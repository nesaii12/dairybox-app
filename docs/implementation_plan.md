# DairyBox Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build DairyBox, a responsive, secure web-based Ordering, Sales, Inventory, and Delivery Management System utilizing Laravel 12, Bootstrap 5, and MySQL.

**Architecture:** A robust Model-View-Controller (MVC) architecture using Laravel's core structures. It features three distinct user roles (Admin, Customer, Rider) managed through route-group middleware, a FIFO-based inventory batch tracking mechanism, a mathematical sales forecasting engine, and a secure reporting suite.

**Tech Stack:** Laravel 12, Bootstrap 5, Vanilla CSS, MySQL, Simple QRCode, DomPDF, Laravel Excel, Chart.js.

---

## Database Schema Design

We will implement the following migrations in MySQL. Foreign keys are explicitly defined to enforce referential integrity, and indices are added for high-traffic query performance.

### 1. `users` Table
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->string('phone')->nullable();
    $table->enum('role', ['admin', 'customer', 'rider'])->default('customer');
    $table->enum('status', ['active', 'restricted'])->default('active');
    $table->timestamp('restricted_until')->nullable(); // For the 3-day restriction rule
    $table->rememberToken();
    $table->timestamps();
    
    $table->index(['email', 'role']);
});
```

### 2. `categories` Table
```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('category_name')->unique();
    $table->timestamps();
});
```

### 3. `products` Table
```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained()->onDelete('cascade');
    $table->string('product_name');
    $table->text('description')->nullable();
    $table->enum('milk_type', ['Carabao', 'Cow']);
    $table->enum('size', ['Small', 'Medium', 'Large']);
    $table->decimal('price', 10, 2);
    $table->string('image')->nullable();
    $table->integer('stock')->default(0);
    $table->date('expiration_date')->nullable();
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->timestamps();
    
    $table->index(['category_id', 'status']);
});
```

### 4. `inventories` Table (FIFO Tracking)
```php
Schema::create('inventories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->integer('stock_in')->default(0);
    $table->integer('stock_out')->default(0);
    $table->integer('remaining_stock')->default(0);
    $table->date('expiration_date')->nullable(); // Expiration date for this specific inventory batch
    $table->string('remarks')->nullable();
    $table->timestamps();
    
    $table->index(['product_id', 'remaining_stock']);
});
```

### 5. `carts` Table
```php
Schema::create('carts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
    $table->timestamps();
});
```

### 6. `cart_items` Table
```php
Schema::create('cart_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cart_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->integer('quantity');
    $table->decimal('subtotal', 10, 2);
    $table->timestamps();
});
```

### 7. `orders` Table
```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('order_number')->unique();
    $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('rider_id')->nullable()->constrained('users')->onDelete('set null');
    $table->enum('order_type', ['Delivery', 'Pickup']);
    $table->string('delivery_address')->nullable();
    $table->date('schedule_date');
    $table->time('schedule_time')->nullable();
    $table->decimal('subtotal', 10, 2);
    $table->decimal('delivery_fee', 10, 2)->default(0.00);
    $table->decimal('total_amount', 10, 2);
    $table->enum('order_status', [
        'Pending', 'Confirmed', 'Preparing', 'Ready for Pickup', 
        'Out for Delivery', 'Delivered', 'Cancelled'
    ])->default('Pending');
    $table->timestamps();
    
    $table->index(['customer_id', 'order_status']);
    $table->index('order_number');
});
```

### 8. `order_items` Table
```php
Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->integer('quantity');
    $table->decimal('price', 10, 2);
    $table->decimal('subtotal', 10, 2);
    $table->timestamps();
});
```

### 9. `deliveries` Table
```php
Schema::create('deliveries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->onDelete('cascade');
    $table->foreignId('rider_id')->constrained('users')->onDelete('cascade');
    $table->enum('delivery_status', ['Out for Delivery', 'Delivered', 'Failed Delivery', 'Cancelled'])->default('Out for Delivery');
    $table->timestamp('delivered_at')->nullable();
    $table->timestamps();
    
    $table->index(['rider_id', 'delivery_status']);
});
```

### 10. `sales` Table
```php
Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->onDelete('cascade');
    $table->decimal('total_amount', 10, 2);
    $table->date('sales_date');
    $table->timestamps();
    
    $table->index('sales_date');
});
```

### 11. `ratings` Table
```php
Schema::create('ratings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->unsignedTinyInteger('rating'); // 1 to 5
    $table->text('review')->nullable();
    $table->timestamps();
    
    $table->index(['product_id', 'rating']);
});
```

### 12. `notifications` Table
```php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('message');
    $table->boolean('is_read')->default(false);
    $table->timestamps();
    
    $table->index(['user_id', 'is_read']);
});
```

### 13. `forecasts` Table
```php
Schema::create('forecasts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->string('forecast_month'); // Format: YYYY-MM
    $table->decimal('predicted_sales', 10, 2);
    $table->timestamps();
    
    $table->index(['product_id', 'forecast_month']);
});
```

### 14. `audit_logs` Table (Security Audit Trail)
```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    $table->string('action'); // e.g., 'STOCK_ADJUSTMENT', 'USER_RESTRICTED', 'ORDER_CANCELLED'
    $table->text('details');
    $table->string('ip_address')->nullable();
    $table->timestamps();
});
```

---

## Core Algorithmic Designs

### 1. FIFO Stock Deduction Algorithm
To satisfy FIFO requirements, we deduct stock from inventory records starting from the oldest active batch with remaining stock.
```php
namespace App\Services;

use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public static function deductStock(int $productId, int $quantity, string $remarks = 'Order Deduction'): void
    {
        DB::transaction(function () use ($productId, $quantity, $remarks) {
            $batches = Inventory::where('product_id', $productId)
                ->where('remaining_stock', '>', 0)
                ->orderBy('created_at', 'asc') // Oldest batches first (FIFO)
                ->lockForUpdate()
                ->get();

            $remaining = $quantity;
            foreach ($batches as $batch) {
                if ($remaining <= 0) break;

                if ($batch->remaining_stock >= $remaining) {
                    $batch->remaining_stock -= $remaining;
                    $batch->stock_out += $remaining;
                    $batch->save();
                    $remaining = 0;
                } else {
                    $remaining -= $batch->remaining_stock;
                    $batch->stock_out += $batch->remaining_stock;
                    $batch->remaining_stock = 0;
                    $batch->save();
                }
            }

            if ($remaining > 0) {
                throw new \Exception("Insufficient stock for product ID {$productId} under FIFO resolution.");
            }

            // Sync overall product stock
            $product = Product::findOrFail($productId);
            $product->stock = max(0, $product->stock - $quantity);
            $product->save();
        });
    }

    public static function addStock(int $productId, int $quantity, ?string $expirationDate = null, string $remarks = 'Stock In'): void
    {
        DB::transaction(function () use ($productId, $quantity, $expirationDate, $remarks) {
            Inventory::create([
                'product_id' => $productId,
                'stock_in' => $quantity,
                'stock_out' => 0,
                'remaining_stock' => $quantity,
                'expiration_date' => $expirationDate,
                'remarks' => $remarks
            ]);

            $product = Product::findOrFail($productId);
            $product->stock += $quantity;
            if ($expirationDate) {
                // Keep the products table expiration_date pointing to the nearest expiring batch
                $nearestBatch = Inventory::where('product_id', $productId)
                    ->where('remaining_stock', '>', 0)
                    ->whereNotNull('expiration_date')
                    ->orderBy('expiration_date', 'asc')
                    ->first();
                $product->expiration_date = $nearestBatch ? $nearestBatch->expiration_date : $expirationDate;
            }
            $product->save();
        });
    }
}
```

### 2. Demand Forecasting Service (Linear Regression + Seasonal Indexing)
A dual-metric approach that analyzes historical monthly product demand:
$$\text{Demand} = \alpha + \beta \times \text{Month} + \text{Seasonal Adjustment}$$
```php
namespace App\Services;

use App\Models\OrderItems;
use App\Models\Sales;
use Illuminate\Support\Facades\DB;

class ForecastService
{
    public static function predictProductDemand(int $productId, string $targetMonthYear): float
    {
        // Fetch last 12 months sales data
        $salesHistory = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m') as month"), DB::raw('SUM(order_items.quantity) as total_qty'))
            ->where('order_items.product_id', $productId)
            ->where('orders.order_status', 'Delivered')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $n = $salesHistory->count();
        if ($n < 3) {
            // Fallback to simple average or 0 if data points are insufficient
            return $salesHistory->avg('total_qty') ?? 0.0;
        }

        // Linear Regression calculation: y = ax + b
        $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
        foreach ($salesHistory as $index => $history) {
            $x = $index + 1; // Month index (1 to N)
            $y = $history->total_qty;
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX ?: 1);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Predict for the next month index (N + 1)
        $predictedIndex = $n + 1;
        $trendForecast = $slope * $predictedIndex + $intercept;

        // Apply a basic seasonal smoothing factor if matching historical months exist
        $targetMonthString = date('m', strtotime($targetMonthYear . '-01'));
        $seasonalData = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.order_status', 'Delivered')
            ->whereMonth('orders.created_at', $targetMonthString)
            ->select(DB::raw('SUM(order_items.quantity) as qty'))
            ->groupBy(DB::raw("DATE_FORMAT(orders.created_at, '%Y')"))
            ->pluck('qty');

        if ($seasonalData->isNotEmpty()) {
            $seasonalAverage = $seasonalData->avg();
            $overallAverage = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $productId)
                ->where('orders.order_status', 'Delivered')
                ->sum('order_items.quantity') / max(1, $n);
                
            $seasonalIndex = $overallAverage > 0 ? ($seasonalAverage / $overallAverage) : 1.0;
            return max(0.0, $trendForecast * $seasonalIndex);
        }

        return max(0.0, $trendForecast);
    }
}
```

---

## Implementation Checklist

### Task 1: Environment Setup & Laravel 12 Initialization
Initialize the standard Laravel 12 framework and set up database parameters.
- **Files:**
  - Create: `config/database.php`
  - Modify: `.env`
  - Modify: `composer.json`
- **Step 1:** Initialize the Laravel project in the workspace.
  ```powershell
  # We assume Laravel installer or composer is run
  composer create-project laravel/laravel:^12.0 . --prefer-dist
  ```
- **Step 2:** Add required dependencies for Simple QRCode, DomPDF, and Laravel Excel.
  ```powershell
  composer require simplesoftwareio/simple-qrcode barryvdh/laravel-dompdf maatwebsite/excel
  ```
- **Step 3:** Configure environment variables in `.env` for database connection, App settings, and mail services.
- **Step 4:** Run migrations to verify database connection works.
  ```powershell
  php artisan migrate
  ```
- **Step 5:** Commit changes.
  ```bash
  git add .env composer.json
  git commit -m "chore: initialize laravel 12 project with core packages"
  ```

---

### Task 2: Database Migrations & Models Configuration
Configure core entities, indexes, and constraints.
- **Files:**
  - Create: Migrations for `categories`, `products`, `inventories`, `orders`, `order_items`, `deliveries`, `sales`, `ratings`, `notifications`, `forecasts`, and `audit_logs`
  - Create: `app/Models/User.php`, `app/Models/Product.php`, `app/Models/Category.php`, `app/Models/Inventory.php`, `app/Models/Order.php`, `app/Models/OrderItem.php`, `app/Models/Delivery.php`, `app/Models/Sale.php`, `app/Models/Rating.php`, `app/Models/Notification.php`, `app/Models/Forecast.php`, `app/Models/AuditLog.php`
- **Step 1:** Define migrations inside `database/migrations/` with correct foreign key mappings, Cascade directives, and indexes for performant lookups.
- **Step 2:** Set up Eloquent models with relationship declarations (`hasMany`, `belongsTo`, etc.).
- **Step 3:** Run migrations.
  ```powershell
  php artisan migrate:fresh
  ```
- **Step 4:** Commit database schemas.
  ```bash
  git add database/migrations/ app/Models/
  git commit -m "feat: design database schemas and models"
  ```

---

### Task 3: Seeders, Factories, and Custom Role Middleware Setup
Write dummy data seeds to assist visual and functional testing, and set up role middlewares.
- **Files:**
  - Create: `database/seeders/DatabaseSeeder.php`
  - Create: Factories for `User`, `Category`, `Product`, `Inventory`, `Order`, `Sales`
  - Create: `app/Http/Middleware/RoleMiddleware.php`
  - Create: `app/Http/Middleware/CheckRestrictionMiddleware.php`
- **Step 1:** Write `RoleMiddleware` to block unauthorized access to dashboard roles (Admin, Customer, Rider).
- **Step 2:** Write `CheckRestrictionMiddleware` to check if a Customer user's `restricted_until` is in the future.
- **Step 3:** Register middlewares in `bootstrap/app.php` or `app/Http/Kernel.php` (depending on Laravel 12 bootstrap rules).
- **Step 4:** Define DatabaseSeeder to seed:
  - 1 Admin user (`admin@dairybox.com`)
  - 1 Rider user (`rider@dairybox.com`)
  - 5 Customer accounts
  - Categories: 'Flavored Milk', 'Ice Cream'
  - 10 mock products with mixed cow/carabao milk settings, Small/Medium/Large sizes
  - Mock sales data over 12 months for testing forecasting logic
- **Step 5:** Run seeding command.
  ```powershell
  php artisan db:seed
  ```
- **Step 6:** Commit.
  ```bash
  git add database/seeders/ app/Http/Middleware/
  git commit -m "feat: setup role middlewares and database seeders"
  ```

---

### Task 4: Frontend Assets & Responsive Shell Integration
Integrate Bootstrap 5, Theme styles (White/Cream/Light Blue palette), and layout shells.
- **Files:**
  - Create: `resources/css/app.css` (Vanilla CSS with variables for Dairy branding)
  - Create: `resources/views/layouts/admin.blade.php` (Admin Sidebar layout)
  - Create: `resources/views/layouts/customer.blade.php` (Customer storefront layout)
  - Create: `resources/views/layouts/rider.blade.php` (Rider optimized shell)
- **Step 1:** Establish visual tokens in `app.css` for background cream tones (`#FDFBF7`), soft light blues (`#E8F1F5`), and dark elements.
- **Step 2:** Design a modern, responsive navigation sidebar for the admin layout, responsive navbar for the customer layout, and clean list-driven views for the mobile-responsive rider dashboard.
- **Step 3:** Confirm compilation of assets.
  ```powershell
  npm install && npm run build
  ```
- **Step 4:** Commit frontend scaffolding.
  ```bash
  git add resources/views/layouts/ resources/css/
  git commit -m "feat: design responsive app layouts using Bootstrap 5 and cream branding"
  ```

---

### Task 5: Admin - Product & Category Management
Build features for Category and Product CRUD, image uploads, and dynamic QR Code generation.
- **Files:**
  - Create: `app/Http/Controllers/Admin/ProductController.php`
  - Create: `app/Http/Controllers/Admin/CategoryController.php`
  - Create: `resources/views/admin/products/index.blade.php`, `resources/views/admin/products/create.blade.php`
- **Step 1:** Implement category list and create controller logic with request validation.
- **Step 2:** Implement product CRUD with image file upload validation (saved to `storage/app/public/products`).
- **Step 3:** Implement QR code generation in product details using simple-qrcode, routing to `/customer/products/{id}`.
- **Step 4:** Verify that storage directory is linked correctly.
  ```powershell
  php artisan storage:link
  ```
- **Step 5:** Commit.
  ```bash
  git add app/Http/Controllers/Admin/ resources/views/admin/
  git commit -m "feat: implement product and category administration with QR codes"
  ```

---

### Task 6: FIFO Inventory Tracking & Stock Alerts
Implement Stock In, Stock Out adjustment, automatic stock synchronization, FIFO tracking logic, and notification triggers.
- **Files:**
  - Modify: `app/Services/InventoryService.php`
  - Create: `app/Http/Controllers/Admin/InventoryController.php`
  - Create: `resources/views/admin/inventory/index.blade.php`
  - Create: `app/Console/Commands/CheckLowStock.php`
- **Step 1:** Create inventory controller to log Stock In and Stock Out (via manual inventory adjustments).
- **Step 2:** Integrate `InventoryService::addStock()` to handle stock additions using FIFO batches.
- **Step 3:** Implement visual alerts on the dashboard/inventory page for low stock (e.g. less than 10 units) and expiration dates approaching (e.g. within 7 days).
- **Step 4:** Create console command to check stock thresholds nightly and trigger notifications to admin users.
- **Step 5:** Commit.
  ```bash
  git add app/Console/Commands/ app/Http/Controllers/Admin/InventoryController.php
  git commit -m "feat: configure FIFO inventory tracking and stock threshold warnings"
  ```

---

### Task 7: Customer storefront - Product Catalog, Cart, & Checkout
Enable customer authentication, product searches/filters, cart additions, and scheduling deliveries.
- **Files:**
  - Create: `app/Http/Controllers/Customer/StoreController.php`
  - Create: `app/Http/Controllers/Customer/CartController.php`
  - Create: `resources/views/customer/catalog.blade.php`, `resources/views/customer/cart.blade.php`, `resources/views/customer/checkout.blade.php`
- **Step 1:** Implement product searching, category filtering, and size selection.
- **Step 2:** Write `CartController` to add, update, and remove items with dynamic calculations.
- **Step 3:** Build checkout forms allowing Customers to select:
  - Delivery/Pickup mode
  - Delivery Address (with delivery fee calculation)
  - Date and Pickup time schedules
- **Step 4:** Run tests to verify checkout form submissions.
- **Step 5:** Commit checkout logic.
  ```bash
  git add app/Http/Controllers/Customer/ resources/views/customer/
  git commit -m "feat: complete storefront catalog, shopping cart, and scheduler checkout"
  ```

---

### Task 8: Order Processing & Status Workflows
Manage orders from confirmation through preparation and assign riders.
- **Files:**
  - Create: `app/Http/Controllers/Admin/OrderController.php`
  - Create: `app/Http/Controllers/Customer/OrderController.php`
  - Create: `resources/views/admin/orders/index.blade.php`, `resources/views/admin/orders/show.blade.php`
- **Step 1:** Implement order creation that runs `InventoryService::deductStock` using FIFO.
- **Step 2:** Create order approval/rejection views in the Admin panel.
- **Step 3:** Provide dropdown mechanisms for Admins to progress order status: Confirmed -> Preparing -> Ready for Pickup / Assigned to Rider.
- **Step 4:** Seed orders and verify status transitions trigger database updates.
- **Step 5:** Commit order manager.
  ```bash
  git add app/Http/Controllers/Admin/OrderController.php resources/views/admin/orders/
  git commit -m "feat: construct order manager workflows and status updating pipelines"
  ```

---

### Task 9: Rider Dashboard & Delivery Handlers
Create the mobile-optimized rider portal, delivery logging, and customer abuse restriction.
- **Files:**
  - Create: `app/Http/Controllers/Rider/RiderController.php`
  - Create: `resources/views/rider/dashboard.blade.php`, `resources/views/rider/history.blade.php`
  - Modify: `app/Http/Controllers/Admin/CustomerController.php` (To support abuse validation restriction)
- **Step 1:** Construct a high-contrast, mobile-friendly interface showing Assigned Deliveries with customer name, phone, address, and total amount.
- **Step 2:** Add buttons for Rider to update status (Delivered, Failed, Cancelled). When marked Delivered, it automatically registers in `sales` table.
- **Step 3:** Implement the Customer Abuse check in the Admin panel. If a rider reports abuse (e.g. repeated fake orders, physical issues) and the admin verifies, the admin can restrict the customer, setting `restricted_until` to `now()->addDays(3)`.
- **Step 4:** Commit rider workflows.
  ```bash
  git add app/Http/Controllers/Rider/ resources/views/rider/
  git commit -m "feat: build rider delivery panels and customer abuse restrictions"
  ```

---

### Task 10: Sales Analytics & Demand Forecasting Module
Integrate dynamic Chart.js dashboards and future demand forecasting.
- **Files:**
  - Create: `app/Http/Controllers/Admin/DashboardController.php`
  - Create: `app/Http/Controllers/Admin/ForecastController.php`
  - Create: `resources/views/admin/dashboard.blade.php`, `resources/views/admin/forecast/index.blade.php`
- **Step 1:** Create Admin Dashboard showing KPIs: Total Sales, Orders, Low Stock Alerts, Expiration Alerts.
- **Step 2:** Implement Chart.js integration for Monthly Sales Graph and Best Selling Products (horizontal bar chart).
- **Step 3:** Implement forecasting view that computes predictions for the upcoming month using `ForecastService` and saves the prediction to the database.
- **Step 4:** Commit.
  ```bash
  git add app/Http/Controllers/Admin/DashboardController.php resources/views/admin/dashboard.blade.php
  git commit -m "feat: deploy administrative dashboards with forecasting modules"
  ```

---

### Task 11: PDF & Excel Reporting Suite
Implement export formats for system managers.
- **Files:**
  - Create: `app/Exports/SalesExport.php`, `app/Exports/InventoryExport.php`
  - Create: `app/Http/Controllers/Admin/ReportController.php`
  - Create: `resources/views/admin/reports/index.blade.php`
- **Step 1:** Implement Excel reports using Laravel Excel package for Sales and Inventory.
- **Step 2:** Implement PDF export using DomPDF layout templates.
- **Step 3:** Create filtering layout to export data based on Date Ranges (Daily, Weekly, Monthly, Annual).
- **Step 4:** Commit reports system.
  ```bash
  git add app/Exports/ app/Http/Controllers/Admin/ReportController.php
  git commit -m "feat: create pdf and excel export services for reports"
  ```

---

### Task 12: Ratings, Reviews, and Real-Time Notifications
Finalize storefront features for product reviews and tracking order progress.
- **Files:**
  - Create: `app/Http/Controllers/Customer/RatingController.php`
  - Create: `app/Http/Controllers/Customer/NotificationController.php`
  - Create: `resources/views/customer/notifications.blade.php`
- **Step 1:** Create review submissions (1-5 rating & feedback text) for purchased products.
- **Step 2:** Create order notification events that dispatch user warnings (e.g. order ready, rider out for delivery).
- **Step 3:** Run final end-to-end user checkout to verify that notifications trigger and stock decreases automatically according to FIFO.
- **Step 4:** Commit final code.
  ```bash
  git add app/Http/Controllers/Customer/RatingController.php
  git commit -m "feat: finalize feedback reviews and order notifications"
  ```

---

## Verification Plan

### Automated Testing
We will configure Feature and Unit tests to verify critical logic.
- **Database Migrations and Models Test:**
  `php artisan test --filter=UserTest`
- **FIFO Inventory Allocation Validation:**
  Create a test to verify stock decrements across multiple batches:
  `php artisan test --filter=InventoryTest`
- **Roles & Permissions Security Middleware Test:**
  `php artisan test --filter=SecurityMiddlewareTest`

### Manual Verification
1. **FIFO Batch Validation:** Add stock of Product A (10 units expiring June 15) and more stock of Product A (20 units expiring June 20). Check out 15 units. Verify that the inventory batch expiring June 15 has 0 units remaining and the batch expiring June 20 has 15 units remaining.
2. **Abuse Verification:** Flag a customer as suspended for abuse. Try to check out with their account and verify that the checkout action is blocked by the restriction middleware.
3. **Forecasting Validation:** Verify forecasted values are generated and plotted on the charts based on the seeded historical sales data.
4. **QR Codes:** Scan generated QR codes using a device simulator to confirm successful redirection to the catalog item detail page.
