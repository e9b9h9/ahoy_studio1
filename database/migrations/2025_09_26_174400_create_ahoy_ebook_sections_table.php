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
        Schema::create('ahoy_ebook_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ebook_id')->references('id')->on('ahoy_ebook_library')->onDelete('cascade');
            $table->unsignedInteger('section_number');
            $table->string('section_type')->default('chapter'); // chapter, page, section, segment, article, paragraph
            $table->string('title')->nullable();
            $table->longText('content'); // Full section text content
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('character_count')->default(0);
            $table->boolean('audio_generated')->default(false);
            $table->timestamps();
            
            // Ensure unique sections per book
            $table->unique(['ebook_id', 'section_number']);
            // Index for quick section lookups
            $table->index(['ebook_id', 'section_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ahoy_ebook_sections');
    }
};