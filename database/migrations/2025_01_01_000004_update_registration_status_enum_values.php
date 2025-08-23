<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change registration_status from ENUM to VARCHAR
        DB::statement("ALTER TABLE businesses MODIFY COLUMN registration_status VARCHAR(50) DEFAULT 'email_verification_pending'");
        
        // Update existing 'completed' values to 'completed_unverified'
        DB::statement("UPDATE businesses SET registration_status = 'completed_unverified' WHERE registration_status = 'completed'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change back to ENUM
        DB::statement("ALTER TABLE businesses MODIFY COLUMN registration_status ENUM(
            'email_verification_pending',
            'cac_info_pending',
            'director_info_pending',
            'completed'
        ) DEFAULT 'email_verification_pending'");
        
        // Update 'completed_unverified' back to 'completed'
        DB::statement("UPDATE businesses SET registration_status = 'completed' WHERE registration_status = 'completed_unverified'");
    }
};
