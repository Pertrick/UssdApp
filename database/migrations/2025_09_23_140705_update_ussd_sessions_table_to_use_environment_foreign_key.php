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
        // First, add the environment_id column to ussd_sessions table
        Schema::table('ussd_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('environment_id')->nullable()->after('ussd_id');
            $table->foreign('environment_id')->references('id')->on('environments')->onDelete('set null');
        });

        // Remove the old environment enum column
        Schema::table('ussd_sessions', function (Blueprint $table) {
            $table->dropColumn('environment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the environment enum column
        Schema::table('ussd_sessions', function (Blueprint $table) {
            $table->enum('environment', ['testing', 'live'])
                  ->default('testing')
                  ->after('ussd_id');
        });

        // Remove the foreign key and environment_id column
        Schema::table('ussd_sessions', function (Blueprint $table) {
            $table->dropForeign(['environment_id']);
            $table->dropColumn('environment_id');
        });
    }
};
