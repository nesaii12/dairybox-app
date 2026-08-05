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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
