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
        Schema::create('codeline_codeline', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requires_codeline_id'); // The codeline that requires the other
            $table->unsignedBigInteger('provides_codeline_id'); // The codeline that provides the variable
            $table->string('variable_name'); // The shared variable name
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('requires_codeline_id')->references('id')->on('master_codelines')->onDelete('cascade');
            $table->foreign('provides_codeline_id')->references('id')->on('master_codelines')->onDelete('cascade');
            
            // Indexes
            $table->index(['requires_codeline_id', 'variable_name']);
            $table->index(['provides_codeline_id', 'variable_name']);
            $table->index('variable_name');
            
            // Prevent duplicate relationships
            $table->unique(['requires_codeline_id', 'provides_codeline_id', 'variable_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codeline_codeline');
    }
};