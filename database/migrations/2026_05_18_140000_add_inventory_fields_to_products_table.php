<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('title');
            $table->string('category', 50)->nullable()->after('sku');
            $table->unsignedInteger('stock_quantity')->default(0)->after('price_cents');
            $table->string('image_url')->nullable()->after('stock_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sku', 'category', 'stock_quantity', 'image_url']);
        });
    }
};
