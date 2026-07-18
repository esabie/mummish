<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_applications', function (Blueprint $table) {
            $table->foreignId('vendor_referrer_id')
                ->nullable()
                ->after('referral_code')
                ->constrained('vendor_referrers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_referrer_id');
        });
    }
};
