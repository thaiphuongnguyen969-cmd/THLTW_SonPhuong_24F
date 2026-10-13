<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('recipient_name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('ward', 100)->nullable();
            $table->text('address_detail');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        $now = now();

        DB::table('users')
            ->whereNotNull('address')
            ->where('address', '<>', '')
            ->orderBy('id')
            ->select('id', 'name', 'phone', 'address', 'created_at', 'updated_at')
            ->chunk(500, function ($users) use ($now) {
                $addresses = [];

                foreach ($users as $user) {
                    $addresses[] = [
                        'user_id' => $user->id,
                        'label' => 'Legacy',
                        'recipient_name' => $user->name,
                        'phone' => $user->phone,
                        'province' => null,
                        'district' => null,
                        'ward' => null,
                        'address_detail' => $user->address,
                        'is_default' => true,
                        'created_at' => $user->created_at ?? $now,
                        'updated_at' => $user->updated_at ?? $now,
                    ];
                }

                DB::table('addresses')->insert($addresses);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
