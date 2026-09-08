<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('health_bookings', 'daily_room_name')
            && ! Schema::hasColumn('health_bookings', 'video_room_name')) {
            Schema::table('health_bookings', function (Blueprint $table) {
                $table->renameColumn('daily_room_name', 'video_room_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('health_bookings', 'video_room_name')
            && ! Schema::hasColumn('health_bookings', 'daily_room_name')) {
            Schema::table('health_bookings', function (Blueprint $table) {
                $table->renameColumn('video_room_name', 'daily_room_name');
            });
        }
    }
};
