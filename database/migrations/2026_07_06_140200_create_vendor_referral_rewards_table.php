<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_referrer_id')->constrained('vendor_referrers')->cascadeOnDelete();
            $table->foreignId('vendor_application_id')->nullable()->constrained('vendor_applications')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->unsignedInteger('amount_cents');
            $table->string('description');
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['vendor_application_id', 'type'], 'vendor_referral_rewards_registration_unique');
            $table->unique('order_item_id', 'vendor_referral_rewards_order_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_referral_rewards');
    }
};
