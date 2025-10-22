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
        Schema::create('variable_codeline', function (Blueprint $table) {
            $table->id();
						$table->unsignedBigInteger('variable_id');	
						$table->unsignedBigInteger('codeline_id');
						$table->foreign('variable_id')->references('id')->on('variables');
						$table->foreign('codeline_id')->references('id')->on('master_codelines');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variable_codeline');
    }
};
