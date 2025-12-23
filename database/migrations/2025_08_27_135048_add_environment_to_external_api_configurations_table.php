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
        Schema::table('external_api_configurations', function (Blueprint $table) {
            $table->enum('environment', ['testing', 'staging', 'production'])->default('production')->after('is_marketplace_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_api_configurations', function (Blueprint $table) {
            $table->dropColumn('environment');
        });
    }
};
