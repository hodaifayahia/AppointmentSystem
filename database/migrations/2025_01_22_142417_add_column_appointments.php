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
        Schema::table('appointments', function (Blueprint $table) {
            // Add a boolean column 'add_to_waitlist' to the 'appointments' table
            $table->boolean('add_to_waitlist')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop the 'add_to_waitlist' column if the migration is rolled back
            $table->dropColumn('add_to_waitlist');
        });
    }
};