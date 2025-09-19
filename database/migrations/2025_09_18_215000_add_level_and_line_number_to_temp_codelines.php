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
            $table->integer('level')->nullable()->after('master_codeline_id');
            $table->integer('line_number')->nullable()->after('level');
            
            $table->index('level');
            $table->index('line_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_codelines', function (Blueprint $table) {
            $table->dropIndex(['level']);
            $table->dropIndex(['line_number']);
            $table->dropColumn('level');
            $table->dropColumn('line_number');
        });
    }
};