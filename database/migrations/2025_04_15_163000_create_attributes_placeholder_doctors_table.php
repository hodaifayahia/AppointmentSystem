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
        Schema::create('attributes_placeholder_doctors', function (Blueprint $table) {
            $table->id();
            // the every doctor have his own attribute for his placeholder
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('placeholder_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes_placeholder_doctors');
    }
};
