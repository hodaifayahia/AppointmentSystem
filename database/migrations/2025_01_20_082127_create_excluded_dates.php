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
        Schema::create('excluded_dates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable(); // Column for Doctor ID
            $table->date('start_date')->nullable(); // Column for Start Date
            $table->date('end_date')->nullable();   // Column for End Date
            $table->string('reason')->nullable(); // Optional reason for exclusion
            $table->boolean('apply_for_all_years'); // Indicates if the exclusion applies to all years
            $table->timestamps(); // Includes created_at and updated_at columns

            // Optional: Add foreign key constraint for `doctor_id`
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');

            // Optional: Add indexes for better performance
            $table->index('doctor_id');
            $table->softDeletes(); 

            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excluded_dates');
    }
};