<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Weekly recurring availability windows.
         * Each row = one working block on a given day of the week.
         * e.g. Monday 09:00–13:00, Monday 14:00–17:00, Thursday 10:00–15:00
         *
         * day_of_week: 0 = Sunday … 6 = Saturday (matches JS Date.getDay())
         */
        if (! Schema::hasTable('health_professional_availability')) {
            Schema::create('health_professional_availability', function (Blueprint $table) {
                $table->id();
                $table->foreignId('health_professional_id')
                    ->constrained('health_professionals')
                    ->cascadeOnDelete();

                $table->unsignedTinyInteger('day_of_week');     // 0–6
                $table->time('start_time');                     // e.g. 09:00:00
                $table->time('end_time');                       // e.g. 13:00:00
                $table->boolean('is_active')->default(true);

                $table->timestamps();
            });
        }

        if (! $this->indexExists('health_professional_availability', 'hp_availability_professional_day_idx')) {
            Schema::table('health_professional_availability', function (Blueprint $table) {
                $table->index(
                    ['health_professional_id', 'day_of_week'],
                    'hp_availability_professional_day_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('health_professional_availability');
    }

    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);

        return count($indexes) > 0;
    }
};
