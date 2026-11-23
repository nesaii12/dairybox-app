<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Database-persisted application settings table
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 120)->unique();
                $table->text('value')->nullable();
                $table->string('type', 30)->default('string');
                $table->string('group', 60)->default('general');
                $table->timestamps();

                $table->index('group');
            });
        }

        // Seed initial default Reservation Cancellation Policy values in the database
        $now = now();
        $defaultSettings = [
            [
                'key' => 'reservation_cancellation_fee_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'reservation_policy',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'reservation_cancellation_fee_percentage',
                'value' => '20',
                'type' => 'decimal',
                'group' => 'reservation_policy',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'reservation_cancellation_policy_description',
                'value' => 'A cancellation fee applies to customer-initiated reservation cancellations and is deducted from the down payment. Full refunds are issued when the cancellation fee is disabled or when DairyBox cancels due to inability to fulfill the order.',
                'type' => 'string',
                'group' => 'reservation_policy',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($defaultSettings as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }

        // 2. Permanent historical cancellation & refund snapshot fields on reservations
        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                if (!Schema::hasColumn('reservations', 'cancellation_initiated_by')) {
                    $table->string('cancellation_initiated_by', 20)->nullable()->after('cancellation_reason');
                }
                if (!Schema::hasColumn('reservations', 'applied_fee_percentage')) {
                    $table->decimal('applied_fee_percentage', 5, 2)->nullable()->after('cancellation_initiated_by');
                }
                if (!Schema::hasColumn('reservations', 'cancellation_fee_amount')) {
                    $table->decimal('cancellation_fee_amount', 10, 2)->nullable()->after('applied_fee_percentage');
                }
                if (!Schema::hasColumn('reservations', 'refund_amount')) {
                    $table->decimal('refund_amount', 10, 2)->nullable()->after('cancellation_fee_amount');
                }
                if (!Schema::hasColumn('reservations', 'refund_status')) {
                    $table->string('refund_status', 30)->nullable()->after('refund_amount');
                    $table->index('refund_status');
                }
                if (!Schema::hasColumn('reservations', 'applied_policy_description')) {
                    $table->text('applied_policy_description')->nullable()->after('refund_status');
                }
                if (!Schema::hasColumn('reservations', 'refund_confirmed_at')) {
                    $table->timestamp('refund_confirmed_at')->nullable()->after('applied_policy_description');
                }
                if (!Schema::hasColumn('reservations', 'refund_confirmed_by')) {
                    $table->foreignId('refund_confirmed_by')->nullable()->after('refund_confirmed_at')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('reservations', 'refund_method')) {
                    $table->string('refund_method', 30)->nullable()->after('refund_confirmed_by');
                }
                if (!Schema::hasColumn('reservations', 'refund_reference')) {
                    $table->string('refund_reference', 100)->nullable()->after('refund_method');
                }
            });
        }

        // 3. Link reservation refunds & forfeited cancellation fees in cash_ledger_entries
        if (Schema::hasTable('cash_ledger_entries') && !Schema::hasColumn('cash_ledger_entries', 'reservation_id')) {
            Schema::table('cash_ledger_entries', function (Blueprint $table) {
                $table->foreignId('reservation_id')->nullable()->after('remittance_id')->constrained('reservations')->nullOnDelete();
                $table->index('entry_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cash_ledger_entries') && Schema::hasColumn('cash_ledger_entries', 'reservation_id')) {
            Schema::table('cash_ledger_entries', function (Blueprint $table) {
                $table->dropForeign(['reservation_id']);
                $table->dropIndex(['entry_type']);
                $table->dropColumn('reservation_id');
            });
        }

        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                if (Schema::hasColumn('reservations', 'refund_confirmed_by')) {
                    $table->dropForeign(['refund_confirmed_by']);
                }

                $columns = array_filter([
                    'cancellation_initiated_by',
                    'applied_fee_percentage',
                    'cancellation_fee_amount',
                    'refund_amount',
                    'refund_status',
                    'applied_policy_description',
                    'refund_confirmed_at',
                    'refund_confirmed_by',
                    'refund_method',
                    'refund_reference',
                ], fn ($col) => Schema::hasColumn('reservations', $col));

                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::dropIfExists('settings');
    }
};
