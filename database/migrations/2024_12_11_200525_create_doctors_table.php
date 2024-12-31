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
        $table->foreignId('specialization_id')->nullable()->constrained('specializations')->onDelete('set null');
        $table->integer('number_of_patient')->default(0);
        $table->enum('frequency', ['Daily', 'Weekly', 'Monthly', 'Custom'])->default('Weekly');
        $table->date('specific_date')->nullable();
        $table->text('notes')->nullable();
        $table->boolean('patients_based_on_time')->default(false);
        $table->integer('time_slot')->nullable();
        $table->integer('appointment_booking_window')->default(1);
        $table->integer('created_by')->default(2);
        $table->unsignedBigInteger('user_id');
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->softDeletes();
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
