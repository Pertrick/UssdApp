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
            $table->boolean('is_billed')->default(false);
            $table->decimal('billing_amount', 10, 4)->nullable();
            $table->string('billing_currency', 3)->default('USD');
            $table->string('billing_status')->default('pending'); // pending, charged, failed, refunded
            $table->timestamp('billed_at')->nullable();
            $table->string('invoice_id')->nullable();
            
            // Indexes for better performance
            $table->index(['is_billed']);
            $table->index(['billing_status']);
            $table->index(['billed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ussd_sessions', function (Blueprint $table) {
            $table->dropIndex(['is_billed']);
            $table->dropIndex(['billing_status']);
            $table->dropIndex(['billed_at']);
            
            $table->dropColumn([
                'is_billed',
                'billing_amount',
                'billing_currency',
                'billing_status',
                'billed_at',
                'invoice_id'
            ]);
        });
    }
};
