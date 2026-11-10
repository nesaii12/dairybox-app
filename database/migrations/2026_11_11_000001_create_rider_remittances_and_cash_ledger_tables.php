<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create rider_remittances table
        if (!Schema::hasTable('rider_remittances')) {
            Schema::create('rider_remittances', function (Blueprint $table) {
                $table->id();
                $table->string('reference_no')->unique();
                $table->foreignId('rider_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
                $table->integer('orders_count')->default(0);
                $table->decimal('expected_amount', 10, 2)->default(0.00);
                $table->decimal('actual_amount', 10, 2)->default(0.00);
                $table->decimal('variance', 10, 2)->default(0.00);
                $table->enum('status', ['confirmed', 'pending_verification', 'rejected'])->default('pending_verification');
                $table->string('payment_method')->default('Cash');
                $table->text('notes')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index(['rider_id', 'status']);
                $table->index('reference_no');
            });
        }

        // 2. Create remittance_orders pivot table
        if (!Schema::hasTable('remittance_orders')) {
            Schema::create('remittance_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('remittance_id')->constrained('rider_remittances')->onDelete('cascade');
                $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
                $table->decimal('amount', 10, 2)->default(0.00);
                $table->timestamps();

                $table->unique(['remittance_id', 'order_id']);
            });
        }

        // 3. Create cash_ledger_entries table
        if (!Schema::hasTable('cash_ledger_entries')) {
            Schema::create('cash_ledger_entries', function (Blueprint $table) {
                $table->id();
                $table->date('ledger_date');
                $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('rider_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('rider_name');
                $table->foreignId('remittance_id')->nullable()->constrained('rider_remittances')->onDelete('set null');
                $table->string('entry_type')->default('RIDER_REMITTANCE');
                $table->decimal('amount_expected', 10, 2)->default(0.00);
                $table->decimal('amount_remitted', 10, 2)->default(0.00);
                $table->decimal('variance', 10, 2)->default(0.00);
                $table->string('payment_method')->default('Cash');
                $table->text('notes')->nullable();
                $table->timestamp('recorded_at');
                $table->timestamps();

                $table->index('ledger_date');
                $table->index('admin_id');
                $table->index('rider_id');
            });
        }

        // 4. Update orders table: allow flexible order_status and add remittance_id
        if (Schema::hasTable('orders')) {
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `order_status` VARCHAR(50) NOT NULL DEFAULT 'Pending'");

            if (!Schema::hasColumn('orders', 'remittance_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->foreignId('remittance_id')->nullable()->after('rider_id')->constrained('rider_remittances')->onDelete('set null');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'remittance_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['remittance_id']);
                $table->dropColumn('remittance_id');
            });
        }

        Schema::dropIfExists('cash_ledger_entries');
        Schema::dropIfExists('remittance_orders');
        Schema::dropIfExists('rider_remittances');
    }
};

