<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('image', 255);
            $table->string('link_url', 2048)->nullable();
            $table->string('button_text', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
