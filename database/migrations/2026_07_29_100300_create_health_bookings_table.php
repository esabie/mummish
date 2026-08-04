<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('health_bookings')) {
            Schema::create('health_bookings', function (Blueprint $table) {
                $table->id();
                $table->string('reference', 16)->unique();      // short public ref e.g. HB-A1B2C3

                $table->foreignId('health_professional_id')
                    ->constrained('health_professionals')
                    ->cascadeOnDelete();

                $table->foreignId('health_professional_service_id')
                    ->constrained('health_professional_services')
                    ->cascadeOnDelete();

                // Patient details (guest booking — no auth required initially)
                $table->string('patient_name');
                $table->string('patient_email');
                $table->string('patient_phone', 20)->nullable();

                // Appointment
                $table->date('appointment_date');
                $table->time('appointment_time');
                $table->string('visit_mode');                   // "Virtual" | "In person"

                // Status: pending | confirmed | cancelled | completed
                $table->string('status', 20)->default('pending');

                $table->text('notes')->nullable();              // optional patient notes
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancellation_reason')->nullable();

                $table->timestamps();
            });
        }

        if (! $this->indexExists('health_bookings', 'hb_prof_date_time_idx')) {
            Schema::table('health_bookings', function (Blueprint $table) {
                $table->index(
                    ['health_professional_id', 'appointment_date', 'appointment_time'],
                    'hb_prof_date_time_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('health_bookings');
    }

    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);

        return count($indexes) > 0;
    }
};
