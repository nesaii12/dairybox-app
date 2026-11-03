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
        if (!Schema::hasColumn('products', 'manufacturing_date')) {
            Schema::table('products', function (Blueprint $table) {
                $table->date('manufacturing_date')->nullable()->after('stock');
            });
        }

        if (!Schema::hasColumn('inventories', 'manufacturing_date')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->date('manufacturing_date')->nullable()->after('remaining_stock');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'manufacturing_date')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('manufacturing_date');
            });
        }

        if (Schema::hasColumn('inventories', 'manufacturing_date')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropColumn('manufacturing_date');
            });
        }
    }
};
