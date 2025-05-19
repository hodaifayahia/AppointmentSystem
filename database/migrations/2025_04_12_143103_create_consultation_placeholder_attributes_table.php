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
        Schema::create('consultation_placeholder_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->onDelete('cascade');
            $table->foreignId('placeholder_id')->constrained('placeholders')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('attributes')->onDelete('cascade');
            $table->text('attribute_value')->nullable();
            // $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_placeholder_attributes');
    }
};
