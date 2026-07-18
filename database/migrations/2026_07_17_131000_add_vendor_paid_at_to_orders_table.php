<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('vendor_paid_at')->nullable()->after('courier_paid_at');
            $table->index('vendor_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['vendor_paid_at']);
            $table->dropColumn('vendor_paid_at');
        });
    }
};
