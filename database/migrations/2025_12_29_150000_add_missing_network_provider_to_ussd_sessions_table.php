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
            
            if (!Schema::hasColumn('ussd_sessions', 'network_provider')) {
                $table->string('network_provider')->nullable()->after('gateway_provider');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ussd_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('ussd_sessions', 'network_provider')) {
                $table->dropColumn('network_provider');
            }
        });
    }
};

