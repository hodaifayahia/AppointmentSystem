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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('specialization');
            $table->enum('days', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])->nullable(); // Allow null for monthly or custom schedules.
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('number_of_patient')->default(0);
            $table->enum('frequency', ['Daily', 'Weekly', 'Monthly', 'Custom'])->default('Weekly');
            $table->date('specific_date')->nullable(); // For custom one-time schedules.
            $table->text('notes')->nullable(); // Additional notes about the schedule.
            $table->boolean('patients_based_on_time')->default(false);
            $table->integer('number_of_patients_per_day')->default(0);
            $table->integer('time_slot')->default(0);
    
            // Add doctor_id foreign key to reference the users table
            $table->unsignedBigInteger('doctor_id');
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
