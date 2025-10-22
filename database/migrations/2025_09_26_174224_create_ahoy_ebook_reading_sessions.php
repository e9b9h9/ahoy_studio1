<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ahoy_ebook_reading_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ebook_id')->references('id')->on('ahoy_ebook_library')->onDelete('cascade');
            $table->unsignedInteger('current_section')->default(1);
            $table->unsignedInteger('current_position')->default(0); // Character position in section
            $table->float('progress_percentage')->default(0); // Overall book progress
            $table->string('voice_preference')->default('alloy'); // OpenAI voice selection
            $table->float('speed_preference')->default(1.0); // Playback speed
            $table->boolean('auto_scroll')->default(true);
            $table->boolean('highlight_text')->default(true);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            
            // Index for faster user book lookups
            $table->index(['user_id', 'ebook_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ahoy_ebook_reading_sessions');
    }
};
