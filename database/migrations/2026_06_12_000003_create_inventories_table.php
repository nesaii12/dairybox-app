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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('stock_in')->default(0);
            $table->integer('stock_out')->default(0);
            $table->integer('remaining_stock')->default(0);
            $table->date('expiration_date')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'remaining_stock']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
