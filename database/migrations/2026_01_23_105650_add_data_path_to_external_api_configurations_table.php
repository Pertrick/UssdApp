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
            $table->string('data_path', 255)->nullable()->after('response_mapping');
            $table->string('error_path', 255)->nullable()->after('data_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_api_configurations', function (Blueprint $table) {
            $table->dropColumn(['data_path', 'error_path']);
        });
    }
};
