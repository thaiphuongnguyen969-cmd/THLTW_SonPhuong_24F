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
                'Migration halted: orders already contains records. Confirm the order-data backfill plan before continuing.'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('role', 20)->default('customer')->after('password');
            $table->string('avatar')->nullable()->after('role');
            $table->string('status', 20)->default('active')->after('avatar');
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verified_at',
                'role',
                'avatar',
                'status',
                'remember_token',
            ]);
        });
    }
};
