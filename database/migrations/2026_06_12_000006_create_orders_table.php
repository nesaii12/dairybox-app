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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
