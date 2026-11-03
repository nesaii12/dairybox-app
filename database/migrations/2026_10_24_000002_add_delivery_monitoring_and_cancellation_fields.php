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
        // 1. Add arrival & cancellation tracking to deliveries table
        Schema::table('deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('deliveries', 'arrived_at')) {
                $table->timestamp('arrived_at')->nullable()->after('delivery_status');
            }
            if (!Schema::hasColumn('deliveries', 'cancelled_after_arrival')) {
                $table->boolean('cancelled_after_arrival')->default(false)->after('arrived_at');
            }
            if (!Schema::hasColumn('deliveries', 'cancellation_reason')) {
                $table->string('cancellation_reason')->nullable()->after('cancelled_after_arrival');
            }
            if (!Schema::hasColumn('deliveries', 'admin_verified_at')) {
                $table->timestamp('admin_verified_at')->nullable()->after('cancellation_reason');
            }
        });

        // 2. Add cancellation tracking to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'cancelled_after_arrival')) {
                $table->boolean('cancelled_after_arrival')->default(false)->after('order_status');
            }
            if (!Schema::hasColumn('orders', 'cancellation_reason')) {
                $table->string('cancellation_reason')->nullable()->after('cancelled_after_arrival');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $cols = ['arrived_at', 'cancelled_after_arrival', 'cancellation_reason', 'admin_verified_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('deliveries', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $cols = ['cancelled_after_arrival', 'cancellation_reason'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
