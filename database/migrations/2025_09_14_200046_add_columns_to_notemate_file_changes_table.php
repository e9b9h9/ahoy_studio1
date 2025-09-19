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
			Schema::table('notemate_file_changes', function (Blueprint $table) {
					$table->string('file_name')->nullable();
					$table->boolean('is_label')->default(false);
					$table->string('folder_path')->nullable();
					$table->unsignedBigInteger('label_id')->nullable();
					
					// Add the foreign key constraint
					$table->foreign('label_id')
								->references('id')
								->on('notemate_file_changes')
								->onDelete('cascade');
			});
	}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notemate_file_changes', function (Blueprint $table) {
            //
        });
    }
};

