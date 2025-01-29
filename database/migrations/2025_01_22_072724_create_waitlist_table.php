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
        Schema::create('waitlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->onDelete('cascade'); // Move nullable() before constrained()
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('specialization_id')->constrained('specializations')->onDelete('cascade');
            $table->boolean('is_Daily');
            $table->integer('created_by');
            $table->integer('appointmentId')->nullable()->constrained('appointments')->onDelete('cascade');
            $table->integer('importance')->nullable()->default(0); // 0 is urgent, 1 is normal, 2 is low
            $table->integer('MoveToEnd')->nullable(); // 0 is urgent, 1 is normal, 2 is low
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlist');
    }
};