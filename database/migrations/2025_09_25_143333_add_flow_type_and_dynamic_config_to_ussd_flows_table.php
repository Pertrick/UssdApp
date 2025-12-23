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
        Schema::table('ussd_flows', function (Blueprint $table) {
            $table->string('flow_type', 20)->default('static')->after('is_active');
            $table->json('dynamic_config')->nullable()->after('flow_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ussd_flows', function (Blueprint $table) {
            $table->dropColumn(['flow_type', 'dynamic_config']);
        });
    }
};
