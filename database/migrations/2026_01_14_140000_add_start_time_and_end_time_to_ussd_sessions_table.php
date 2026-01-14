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
        Schema::table('ussd_sessions', function (Blueprint $table) {
            // Add start_time and end_time columns if they don't exist
            if (!Schema::hasColumn('ussd_sessions', 'start_time')) {
                $table->timestamp('start_time')->nullable()->after('status');
            }
            if (!Schema::hasColumn('ussd_sessions', 'end_time')) {
                $table->timestamp('end_time')->nullable()->after('start_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ussd_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('ussd_sessions', 'start_time')) {
                $table->dropColumn('start_time');
            }
            if (Schema::hasColumn('ussd_sessions', 'end_time')) {
                $table->dropColumn('end_time');
            }
        });
    }
};
