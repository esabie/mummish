<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('health_bookings', 'meeting_url')) {
                $table->string('meeting_url', 500)->nullable()->after('video_room_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('health_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('health_bookings', 'meeting_url')) {
                $table->dropColumn('meeting_url');
            }
        });
    }
};
