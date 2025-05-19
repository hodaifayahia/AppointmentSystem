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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the template
            $table->string('file_path'); // Path to the uploaded Word document
            $table->string('mime_type')->nullable(); // MIME type of the file (e.g., 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            $table->unsignedBigInteger('file_size')->nullable(); // File size in bytes            $table->foreignId('folder_id')->nullable()->constrained()->onDelete('set null'); // Link to folder
            // for doctor id
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->onDelete('set null'); // Link to doctor
            $table->string('description')->nullable(); // Description of the template
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
