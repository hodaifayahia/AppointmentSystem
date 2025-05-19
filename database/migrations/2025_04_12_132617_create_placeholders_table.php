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
        Schema::create('placeholders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // the every doctor have his own placeholder 
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('specializations_id')->nullable()->constrained('specializations')->onDelete('cascade');
            // the every placeholder have his own type
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('placeholders');
    }
};
