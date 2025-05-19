<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Modify the 'role' column to include 'SuperAdmin'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'receptionist', 'doctor', 'SuperAdmin') NOT NULL DEFAULT 'admin'");
    }

    public function down(): void
    {
        // Revert back to the original enum without 'SuperAdmin'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'receptionist', 'doctor') NOT NULL DEFAULT 'admin'");
    }
};

