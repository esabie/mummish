<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_bookings', function (Blueprint $table) {
            $table->string('join_token', 64)->nullable()->unique()->after('cancellation_reason');
            $table->string('video_room_name')->nullable()->after('join_token');
            $table->string('meeting_location')->nullable()->after('video_room_name');
            $table->string('meeting_whatsapp', 30)->nullable()->after('meeting_location');
            $table->text('logistics_notes')->nullable()->after('meeting_whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('health_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'join_token',
                'video_room_name',
                'meeting_location',
                'meeting_whatsapp',
                'logistics_notes',
            ]);
        });
    }
};
