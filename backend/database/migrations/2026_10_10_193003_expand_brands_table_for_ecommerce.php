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
        Schema::table('brands', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->string('status', 20)->default('active')->after('slug');
        });

        $rows = DB::table('brands')->select('id', 'name')->orderBy('id')->get();
        $bases = [];

        foreach ($rows as $row) {
            $bases[$row->id] = Str::slug((string) $row->name) ?: 'brand-'.$row->id;
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
            DB::table('brands')->where('id', $row->id)->update(['slug' => $slug]);
        }

        Schema::table('brands', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique('brands_slug_unique');
            $table->dropColumn(['slug', 'status']);
        });
    }
};
