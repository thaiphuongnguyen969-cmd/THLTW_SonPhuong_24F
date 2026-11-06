<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->decimal('sale_price', 12, 2)->nullable()->after('price');
            $table->string('status', 20)->default('active')->after('sale_price');
            $table->softDeletes();
        });

        $rows = DB::table('products')->select('id', 'name')->orderBy('id')->get();
        $bases = [];

        foreach ($rows as $row) {
            $bases[$row->id] = Str::slug((string) $row->name) ?: 'product-'.$row->id;
        }

        $counts = array_count_values(array_values($bases));
        $reservedBases = [];

        foreach ($bases as $id => $base) {
            if ($counts[$base] === 1) {
                $reservedBases[$base] = true;
            }
        }

        $used = [];

        foreach ($rows as $row) {
            $base = $bases[$row->id];
            $needsSuffix = $counts[$base] > 1;
            $slug = $needsSuffix ? $base.'-'.$row->id : $base;
            $suffix = 1;

            while (isset($used[$slug]) || ($slug !== $base && isset($reservedBases[$slug]))) {
                $slug = $base.'-'.$row->id.'-'.$suffix++;
            }

            $used[$slug] = true;
            DB::table('products')->where('id', $row->id)->update(['slug' => $slug]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unique('slug');
            $table->dropForeign('products_category_id_foreign');
            $table->foreign('category_id', 'products_category_id_foreign')
                ->references('id')
                ->on('categories')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_category_id_foreign');
            $table->foreign('category_id', 'products_category_id_foreign')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();
            $table->dropUnique('products_slug_unique');
            $table->dropSoftDeletes();
            $table->dropColumn(['slug', 'sale_price', 'status']);
        });
    }
};
