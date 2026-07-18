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
            $table->timestamp('delivered_at')->nullable()->after('shipped_to_customer_at');
        });

        // Preserve existing wallet releases: previously "shipped" meant escrow released.
        DB::table('vendor_order_fulfillments')
            ->where('status', VendorFulfillmentStatus::ShippedToCustomer->value)
            ->update([
                'status' => VendorFulfillmentStatus::Delivered->value,
                'delivered_at' => DB::raw('COALESCE(shipped_to_customer_at, fulfilled_at, CURRENT_TIMESTAMP)'),
            ]);
    }

    public function down(): void
    {
        DB::table('vendor_order_fulfillments')
            ->where('status', VendorFulfillmentStatus::Delivered->value)
            ->update([
                'status' => VendorFulfillmentStatus::ShippedToCustomer->value,
            ]);

        Schema::table('vendor_order_fulfillments', function (Blueprint $table) {
            $table->dropColumn('delivered_at');
        });
    }
};
