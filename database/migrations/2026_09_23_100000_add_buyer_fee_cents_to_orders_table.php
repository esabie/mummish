<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'buyer_fee_cents')) {
                $table->unsignedInteger('buyer_fee_cents')->default(0)->after('discount_cents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'buyer_fee_cents')) {
                $table->dropColumn('buyer_fee_cents');
            }
        });
    }
};
