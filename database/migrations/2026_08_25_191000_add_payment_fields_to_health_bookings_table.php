<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('health_bookings', 'amount_cents')) {
                $table->unsignedInteger('amount_cents')->default(0)->after('notes');
            }
            if (! Schema::hasColumn('health_bookings', 'commission_cents')) {
                $table->unsignedInteger('commission_cents')->default(0)->after('amount_cents');
            }
            if (! Schema::hasColumn('health_bookings', 'professional_payout_cents')) {
                $table->unsignedInteger('professional_payout_cents')->default(0)->after('commission_cents');
            }
            if (! Schema::hasColumn('health_bookings', 'payment_status')) {
                $table->string('payment_status', 20)->default('pending')->after('professional_payout_cents');
            }
            if (! Schema::hasColumn('health_bookings', 'paystack_reference')) {
                $table->string('paystack_reference', 64)->nullable()->unique()->after('payment_status');
            }
            if (! Schema::hasColumn('health_bookings', 'paystack_transaction_id')) {
                $table->string('paystack_transaction_id', 64)->nullable()->after('paystack_reference');
            }
            if (! Schema::hasColumn('health_bookings', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('paystack_transaction_id');
            }
            if (! Schema::hasColumn('health_bookings', 'payment_expires_at')) {
                $table->timestamp('payment_expires_at')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('health_bookings', 'professional_paid_at')) {
                $table->timestamp('professional_paid_at')->nullable()->after('cancellation_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('health_bookings', function (Blueprint $table) {
            $columns = [
                'amount_cents',
                'commission_cents',
                'professional_payout_cents',
                'payment_status',
                'paystack_reference',
                'paystack_transaction_id',
                'paid_at',
                'payment_expires_at',
                'professional_paid_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('health_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
