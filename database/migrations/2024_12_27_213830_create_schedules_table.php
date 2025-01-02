<?php

use App\DayOfWeekEnum;
use App\ShiftPeriodEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->enum('day_of_week', array_column(DayOfWeekEnum::cases(), 'value'));
            $table->enum('shift_period', array_column(ShiftPeriodEnum::cases(), 'value'));
            $table->time('start_time');
            $table->time('end_time');
            $table->date('date')->nullable();
            $table->integer('number_of_patients_per_day')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('break_duration')->nullable()->comment('Break duration in minutes');
            $table->json('break_times')->nullable()->comment('JSON array of break start times');
            
            // Schedule exceptions
            $table->json('excluded_dates')->nullable()->comment('Dates when this schedule does not apply');
            $table->json('modified_times')->nullable()->comment('Special timing modifications for specific dates');
            
            // Soft deletes for historical records
            $table->softDeletes();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            // Constraints
            $table->unique(['doctor_id', 'day_of_week', 'shift_period', 'deleted_at']);
          });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};