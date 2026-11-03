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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->default('cod')->after('order_type'); // 'cod', 'xendit'
            $table->string('payment_status')->default('pending')->after('payment_method'); // 'pending', 'paid', 'failed', 'expired'
            $table->string('payment_channel')->nullable()->after('payment_status'); // 'GCASH', 'PAYMAYA', 'CREDIT_CARD', etc.
            $table->string('xendit_invoice_id')->nullable()->index()->after('payment_channel');
            $table->text('xendit_invoice_url')->nullable()->after('xendit_invoice_id');
            $table->timestamp('paid_at')->nullable()->after('xendit_invoice_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['xendit_invoice_id']);
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'payment_channel',
                'xendit_invoice_id',
                'xendit_invoice_url',
                'paid_at',
            ]);
        });
    }
};
