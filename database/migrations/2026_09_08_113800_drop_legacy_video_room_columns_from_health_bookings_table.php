<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_bookings', function (Blueprint $table) {
            $columns = collect(['join_token', 'video_room_name'])
                ->filter(fn (string $column) => Schema::hasColumn('health_bookings', $column))
                ->values()
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('health_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('health_bookings', 'join_token')) {
                $table->string('join_token', 64)->nullable()->unique()->after('cancellation_reason');
            }

            if (! Schema::hasColumn('health_bookings', 'video_room_name')) {
                $after = Schema::hasColumn('health_bookings', 'join_token') ? 'join_token' : 'cancellation_reason';
                $table->string('video_room_name')->nullable()->after($after);
            }
        });
    }
};
