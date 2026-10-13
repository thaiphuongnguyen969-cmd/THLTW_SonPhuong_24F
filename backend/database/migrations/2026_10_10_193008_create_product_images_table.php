<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->string('image_url');
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('products')
            ->whereNotNull('image')
            ->where('image', '<>', '')
            ->orderBy('id')
            ->select('id', 'image', 'created_at', 'updated_at')
            ->chunk(500, function ($products) use ($now) {
                $images = [];

                foreach ($products as $product) {
                    $images[] = [
                        'product_id' => $product->id,
                        'image_url' => $product->image,
                        'is_primary' => true,
                        'sort_order' => 0,
                        'created_at' => $product->created_at ?? $now,
                        'updated_at' => $product->updated_at ?? $now,
                    ];
                }

                DB::table('product_images')->insert($images);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
