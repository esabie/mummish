<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('image_urls')->nullable()->after('image_url');
        });

        $products = DB::table('products')
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->get(['id', 'image_url']);

        foreach ($products as $product) {
            DB::table('products')
                ->where('id', $product->id)
                ->update(['image_urls' => json_encode([$product->image_url])]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image_urls');
        });
    }
};
