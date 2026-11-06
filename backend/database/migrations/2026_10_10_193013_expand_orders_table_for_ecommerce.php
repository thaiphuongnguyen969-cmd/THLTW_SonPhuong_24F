<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('orders')->exists()) {
            throw new RuntimeException(
                'Migration halted: orders contains records. Confirm how to populate the new order fields before continuing.'
            );
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_code', 100)->nullable()->unique();
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->decimal('shipping_fee', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_status', 50)->nullable();
            $table->text('shipping_address_detail')->nullable();
            $table->string('shipping_phone', 20)->nullable();
            $table->string('promotion_code', 100)->nullable();
            $table->text('note')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->foreignId('promotion_id')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_user_id_foreign');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id', 'orders_user_id_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('promotion_id', 'orders_promotion_id_foreign')
                ->references('id')
                ->on('promotions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('orders')->exists()) {
            throw new RuntimeException(
                'Rollback halted: orders contains records that may rely on the ecommerce columns being removed.'
            );
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_user_id_foreign');
            $table->dropForeign('orders_promotion_id_foreign');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id', 'orders_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->dropUnique('orders_order_code_unique');
            $table->dropColumn([
                'order_code',
                'subtotal',
                'discount_amount',
                'shipping_fee',
                'total_amount',
                'payment_method',
                'payment_status',
                'shipping_address_detail',
                'shipping_phone',
                'promotion_code',
                'note',
                'cancelled_reason',
                'promotion_id',
            ]);
        });
    }
};
