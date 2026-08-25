<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_professional_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_professional_id')
                ->constrained('health_professionals')
                ->cascadeOnDelete();

            $table->string('name');                         // e.g. "Initial consultation"
            $table->string('visit_mode');                   // "Virtual" | "In person"
            $table->unsignedInteger('price_cedis');         // e.g. 250
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_professional_services');
    }
};
