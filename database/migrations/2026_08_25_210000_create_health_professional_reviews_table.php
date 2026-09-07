<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('health_professional_reviews')) {
            return;
        }

        Schema::create('health_professional_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('health_professional_id')->constrained()->cascadeOnDelete();
            $table->string('patient_name');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['health_professional_id', 'created_at'], 'hp_reviews_professional_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_professional_reviews');
    }
};
