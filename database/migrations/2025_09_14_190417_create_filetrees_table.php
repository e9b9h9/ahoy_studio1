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
        Schema::create('filetrees', function (Blueprint $table) {
            $table->id();
						$table->string('path');
						$table->boolean('is_folder');
						$table->string('name');
						$table->unsignedBigInteger('parent_id')->nullable()->change();
						$table->string('project_folder_name');
						$table->integer('project_folder_name_count')->nullable()->change();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filetrees');
    }
};
