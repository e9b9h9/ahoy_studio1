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
        Schema::table('codeline_codeblock', function (Blueprint $table) {
            $table->foreign('parent_codeline_id')->references('id')->on('master_codelines')->onDelete('cascade');
            
            $table->index('parent_codeline_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('codeline_codeblock', function (Blueprint $table) {
            $table->dropForeign(['parent_codeline_id']);
            $table->dropIndex(['parent_codeline_id']);
        });
    }
};