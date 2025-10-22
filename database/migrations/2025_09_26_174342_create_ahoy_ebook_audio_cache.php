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
        Schema::create('ahoy_ebook_audio_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ebook_id')->references('id')->on('ahoy_ebook_library')->onDelete('cascade');
            $table->unsignedInteger('section_number');
            $table->unsignedInteger('chunk_number'); // For splitting long sections
            $table->string('voice', 20); // OpenAI voice used
            $table->float('speed'); // Speed setting used
            $table->string('audio_file_path'); // Path to cached audio file
            $table->unsignedInteger('duration_seconds')->nullable(); // Audio duration
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->text('text_content'); // The text that was converted
            $table->unsignedInteger('character_start'); // Start position in section
            $table->unsignedInteger('character_end'); // End position in section
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            
            // Unique constraint to prevent duplicate audio generation
            $table->unique(['ebook_id', 'section_number', 'chunk_number', 'voice', 'speed'], 'unique_audio_chunk');
            // Index for cleanup of old unused files
            $table->index('last_accessed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ahoy_ebook_audio_cache');
    }
};
