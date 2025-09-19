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
        Schema::table('temp_codelines', function (Blueprint $table) {
            $table->json('variables')->nullable();
            $table->string('purpose_key')->nullable();
            $table->string('file_location')->nullable();
            $table->boolean('is_opener')->nullable();
            $table->boolean('is_closer')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
