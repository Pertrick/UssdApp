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
            $table->unsignedBigInteger('gateway_cost')->nullable()->after('billing_amount');
            $table->string('gateway_cost_currency', 3)->nullable()->after('gateway_cost');
            $table->string('gateway_provider')->nullable()->after('gateway_cost_currency');
            $table->string('network_provider')->nullable()->after('gateway_provider');
            $table->timestamp('gateway_cost_recorded_at')->nullable()->after('network_provider');
            
            $table->index('gateway_provider');
            $table->index('gateway_cost_recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ussd_sessions', function (Blueprint $table) {
            $table->dropIndex(['gateway_provider']);
            $table->dropIndex(['gateway_cost_recorded_at']);
            $table->dropColumn([
                'gateway_cost',
                'gateway_cost_currency',
                'gateway_provider',
                'network_provider',
                'gateway_cost_recorded_at',
            ]);
        });
    }
};
