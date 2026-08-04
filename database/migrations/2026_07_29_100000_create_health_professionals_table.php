<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_professionals', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('title');                        // e.g. "Pediatrician"
            $table->string('specialty');                    // e.g. "Pediatrics"
            $table->text('about')->nullable();

            // Contact & location
            $table->string('location')->nullable();         // e.g. "East Legon, Accra"
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();

            // Visit options — stored as JSON array e.g. ["Virtual","In person"]
            $table->json('visit_modes')->nullable();

            // Languages — JSON array e.g. ["English","Twi"]
            $table->json('languages')->nullable();

            // Care highlights — JSON array of bullet strings
            $table->json('highlights')->nullable();

            // Booking metadata
            $table->string('booking_note')->nullable();
            $table->string('response_time')->nullable();    // e.g. "< 15 minutes"
            $table->string('experience')->nullable();       // e.g. "10+ years in pediatric care"

            // Media
            $table->string('image_path')->nullable();       // local storage path
            $table->string('image_url')->nullable();        // fallback external URL

            // Ratings (denormalised for fast reads; updated when reviews are added)
            $table->unsignedSmallInteger('review_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);

            // Admin control
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_professionals');
    }
};
