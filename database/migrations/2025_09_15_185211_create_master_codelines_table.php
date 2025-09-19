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
    Schema::create('master_codelines', function (Blueprint $table) {
        $table->id();
        $table->text('codeline'); 
				$table->text('comment'); 
				$table->json('variables');
        $table->string('purpose_key');
				$table->string('file_location'); //count page set up tags and order with the tag#_codeline number with the  useful for pages with more than one language because the codelines should order by typical tag locations like <script setup> tag would be located at the top
				$table->boolean('is_opener'); 
				$table->boolean('is_closer'); 
        $table->unsignedBigInteger('language_id')->nullable();
        $table->foreign('language_id')->references('id')->on('languages')->onDelete('cascade');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_codelines');
    }
};
