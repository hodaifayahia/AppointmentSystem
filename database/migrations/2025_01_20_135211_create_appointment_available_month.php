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
        Schema::create('appointment_available_month', function (Blueprint $table) {
            $table->id();
            $table->integer('month');
            $table->integer('year');
            $table->integer('doctor_id');
            $table->boolean('is_available');
            $table->softDeletes(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_available_month');
    }
};
