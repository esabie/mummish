<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_applications', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('category');
            $table->string('bank_name')->nullable()->after('payment_method');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number')->nullable()->after('bank_account_name');
            $table->string('mobile_money_provider')->nullable()->after('bank_account_number');
            $table->string('mobile_money_name')->nullable()->after('mobile_money_provider');
            $table->string('mobile_money_number')->nullable()->after('mobile_money_name');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_applications', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'bank_name',
                'bank_account_name',
                'bank_account_number',
                'mobile_money_provider',
                'mobile_money_name',
                'mobile_money_number',
            ]);
        });
    }
};
