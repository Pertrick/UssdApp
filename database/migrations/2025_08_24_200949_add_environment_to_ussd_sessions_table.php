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
            $table->enum('environment', ['testing', 'live'])->default('testing')->after('ussd_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ussd_sessions', function (Blueprint $table) {
            $table->dropColumn('environment');
        });
    }
};
