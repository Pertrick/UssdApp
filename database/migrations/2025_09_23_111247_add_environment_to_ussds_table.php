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
        Schema::table('ussds', function (Blueprint $table) {
            $table->enum('environment', ['simulation', 'testing', 'production'])
                  ->default('simulation')
                  ->after('is_active')
                  ->comment('Environment mode: simulation (mock API calls), testing (real API calls in test mode), production (live API calls)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ussds', function (Blueprint $table) {
            $table->dropColumn('environment');
        });
    }
};
