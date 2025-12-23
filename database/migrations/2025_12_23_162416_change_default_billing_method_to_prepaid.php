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
        // Change default billing_method from 'postpaid' to 'prepaid' for new businesses
        DB::statement("ALTER TABLE businesses MODIFY COLUMN billing_method VARCHAR(20) DEFAULT 'prepaid'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to 'postpaid' as default
        DB::statement("ALTER TABLE businesses MODIFY COLUMN billing_method VARCHAR(20) DEFAULT 'postpaid'");
    }
};
