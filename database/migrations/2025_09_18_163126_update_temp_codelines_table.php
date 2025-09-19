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
            $table->unsignedBigInteger('master_codeline_id')->nullable()->after('language_id');
            
            $table->foreign('master_codeline_id')->references('id')->on('master_codelines')->onDelete('set null');
            
            $table->index('master_codeline_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_codelines', function (Blueprint $table) {
            $table->dropForeign(['master_codeline_id']);
            $table->dropIndex(['master_codeline_id']);
            $table->dropColumn('master_codeline_id');
        });
    }
};
