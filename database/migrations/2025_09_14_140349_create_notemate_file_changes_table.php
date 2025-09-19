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
        Schema::create('notemate_file_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('codefolder_id')->constrained('notemate_codefolders')->onDelete('cascade');
            $table->string('file_path');
            $table->enum('change_type', ['new', 'modified', 'deleted']);
            $table->timestamp('detected_at');
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['codefolder_id', 'detected_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notemate_file_changes');
    }
};