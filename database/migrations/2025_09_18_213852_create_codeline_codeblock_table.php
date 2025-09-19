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
        Schema::create('codeline_codeblock', function (Blueprint $table) {
            $table->id();
						$table->unsignedBigInteger('codeline_id');
						$table->unsignedBigInteger('codeblock_id');
						$table->unsignedBigInteger('parent_codeline_id');
						$table->foreign('codeline_id')->references('id')->on('master_codelines');
						$table->foreign('codeblock_id')->references('id')->on('codeblocks');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codeline_codeblock');
    }
};
