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
            $table->timestamp('sold_out_at')->nullable()->after('stock_quantity');
        });

        // Start the clock now for products that are already sold out.
        DB::table('products')
            ->where('stock_quantity', 0)
            ->update(['sold_out_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sold_out_at');
        });
    }
};
