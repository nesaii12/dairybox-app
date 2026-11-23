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
        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                if (!Schema::hasColumn('reservations', 'fulfillment_type')) {
                    $table->string('fulfillment_type', 20)->default('Pickup')->after('status');
                }
                if (!Schema::hasColumn('reservations', 'delivery_address')) {
                    $table->string('delivery_address', 255)->nullable()->after('fulfillment_type');
                }
                if (!Schema::hasColumn('reservations', 'rider_id')) {
                    $table->foreignId('rider_id')->nullable()->after('delivery_address')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('reservations', 'delivery_fee')) {
                    $table->decimal('delivery_fee', 10, 2)->default(0.00)->after('total_amount');
                }
                if (!Schema::hasColumn('reservations', 'dispatched_at')) {
                    $table->timestamp('dispatched_at')->nullable()->after('ready_at');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'down_payment_paid')) {
                    $table->decimal('down_payment_paid', 10, 2)->default(0.00)->after('total_amount');
                }
                if (!Schema::hasColumn('orders', 'pending_balance')) {
                    $table->decimal('pending_balance', 10, 2)->nullable()->after('down_payment_paid');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $cols = array_filter(['down_payment_paid', 'pending_balance'], fn ($c) => Schema::hasColumn('orders', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                if (Schema::hasColumn('reservations', 'rider_id')) {
                    $table->dropForeign(['rider_id']);
                }
                $cols = array_filter([
                    'fulfillment_type',
                    'delivery_address',
                    'rider_id',
                    'delivery_fee',
                    'dispatched_at',
                ], fn ($c) => Schema::hasColumn('reservations', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
