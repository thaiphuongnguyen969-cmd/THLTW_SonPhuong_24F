<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignId('size_id')
                ->constrained('sizes')
                ->restrictOnDelete();
            $table->foreignId('color_id')
                ->constrained('colors')
                ->restrictOnDelete();
            $table->string('sku', 100)->unique();
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(
                ['product_id', 'size_id', 'color_id'],
                'product_variants_product_size_color_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
