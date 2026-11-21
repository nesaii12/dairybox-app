<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'reserved_stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('reserved_stock')->default(0)->after('stock');
            });
        }

        if (!Schema::hasTable('reservations')) {
            Schema::create('reservations', function (Blueprint $table) {
                $table->id();
                $table->string('reservation_code', 32)->unique();
                $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('customer_name', 150);
                $table->string('contact_number', 50);
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('size', 20)->nullable();
                $table->unsignedInteger('quantity');
                $table->unsignedInteger('allocated_quantity')->default(0);
                $table->decimal('unit_price', 10, 2);
                $table->decimal('total_amount', 10, 2);
                $table->decimal('down_payment', 10, 2)->default(0.00);
                $table->decimal('remaining_balance', 10, 2)->default(0.00);
                $table->string('payment_status', 30)->default('Unpaid');
                $table->string('status', 30)->default('Pending');
                $table->date('reservation_date');
                $table->date('pickup_date');
                $table->text('notes')->nullable();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('ready_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancellation_reason', 255)->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('pickup_date');
                $table->index('reservation_date');
                $table->index(['product_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'reserved_stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('reserved_stock');
            });
        }
    }
};
