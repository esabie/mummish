<?php

use App\Enums\VendorApplicationStatus;
use App\Models\VendorApplication;
use App\Services\ShopSlugGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_applications', function (Blueprint $table) {
            $table->string('shop_slug')->nullable()->unique()->after('shop_name');
        });

        $generator = app(ShopSlugGenerator::class);

        VendorApplication::query()
            ->where('status', VendorApplicationStatus::Approved)
            ->whereNull('shop_slug')
            ->each(function (VendorApplication $application) use ($generator): void {
                $application->update([
                    'shop_slug' => $generator->generate($application->shop_name, $application->id),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('vendor_applications', function (Blueprint $table) {
            $table->dropUnique(['shop_slug']);
            $table->dropColumn('shop_slug');
        });
    }
};
