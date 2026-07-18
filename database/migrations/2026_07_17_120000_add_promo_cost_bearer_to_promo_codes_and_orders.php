<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->string('cost_bearer')->default('mummish')->after('is_active');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('promo_cost_bearer')->nullable()->after('discount_cents');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('promo_cost_bearer');
        });

        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropColumn('cost_bearer');
        });
    }
};
