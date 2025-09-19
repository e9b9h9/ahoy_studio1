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
        Schema::create('notemate_snippetfeatures', function (Blueprint $table) {
            $table->id();
						$table->string('snippetfeature');
						$table->unsignedBigInteger('parent_id')->nullable();
						$table->foreign('parent_id')->references('id')->on('notemate_snippetfeatures')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notemate_snippetfeatures');
    }
};
