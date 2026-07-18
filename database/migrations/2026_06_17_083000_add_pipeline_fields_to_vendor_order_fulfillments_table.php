<?php

use App\Enums\VendorFulfillmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_order_fulfillments', function (Blueprint $table) {
            $table->string('status')->default(VendorFulfillmentStatus::ReadyForPickup->value)->after('vendor_user_id');
            $table->timestamp('ready_for_pickup_at')->nullable()->after('status');
            $table->timestamp('picked_up_at')->nullable()->after('ready_for_pickup_at');
            $table->timestamp('received_at_warehouse_at')->nullable()->after('picked_up_at');
            $table->timestamp('shipped_to_customer_at')->nullable()->after('received_at_warehouse_at');
        });

        DB::table('vendor_order_fulfillments')
            ->whereNull('ready_for_pickup_at')
            ->update([
                'status' => VendorFulfillmentStatus::ReadyForPickup->value,
                'ready_for_pickup_at' => DB::raw('fulfilled_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('vendor_order_fulfillments', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'ready_for_pickup_at',
                'picked_up_at',
                'received_at_warehouse_at',
                'shipped_to_customer_at',
            ]);
        });
    }
};
