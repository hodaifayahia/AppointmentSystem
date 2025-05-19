<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('appointment_forcers', function (Blueprint $table) {
            // Add new columns
            $table->integer('number_of_patients')->after('is_able_to_force'); // Number of patients
            $table->time('start_time')->after('number_of_patients');          // Start time
            $table->time('end_time')->after('start_time');                    // End time

            // Make user_id nullable
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('appointment_forcers', function (Blueprint $table) {
            // Drop the added columns
            $table->dropColumn('number_of_patients');
            $table->dropColumn('start_time');
            $table->dropColumn('end_time');

            // Revert user_id to non-nullable
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};