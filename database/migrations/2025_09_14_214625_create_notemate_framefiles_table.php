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
        Schema::create('notemate_framefiles', function (Blueprint $table) {
            $table->id();
						$table->string('framefile_name');
						$table->string('framefile_path');
						$table->string('framefile_extension')->nullable();
						$table->boolean('is_framefolder');
						$table->unsignedBigInteger('parent_id')->nullable();
						$table->foreign('parent_id')->references('id')->on('notemate_framefiles')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notemate_framefiles');
    }
};
