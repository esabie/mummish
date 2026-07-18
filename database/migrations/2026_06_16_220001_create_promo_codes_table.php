<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('type');
            $table->unsignedInteger('value');
            $table->unsignedInteger('min_subtotal_cents')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('promo_code', 40)->nullable()->after('shipping_cents');
            $table->unsignedInteger('discount_cents')->default(0)->after('promo_code');
        });

        DB::table('promo_codes')->insert([
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => 10,
            'min_subtotal_cents' => null,
            'max_uses' => null,
            'uses_count' => 0,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['promo_code', 'discount_cents']);
        });

        Schema::dropIfExists('promo_codes');
    }
};
