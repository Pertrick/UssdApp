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
        Schema::table('businesses', function (Blueprint $table) {
            $table->decimal('account_balance', 15, 4)->default(0.0000);
            $table->string('billing_currency', 3)->default('USD');
            $table->decimal('session_price', 10, 4)->default(0.0200); // Default $0.02 per session
            $table->boolean('billing_enabled')->default(true);
            
            // Indexes for better performance
            $table->index(['account_balance']);
            $table->index(['billing_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['account_balance']);
            $table->dropIndex(['billing_enabled']);
            
            $table->dropColumn([
                'account_balance',
                'billing_currency',
                'session_price',
                'billing_enabled'
            ]);
        });
    }
};
