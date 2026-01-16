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
        Schema::create('network_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('country', 2)->default('NG');
            $table->string('network'); // MTN, Airtel, Glo, 9mobile
            $table->decimal('markup_percentage', 5, 2)->default(50.00)->comment('Profit margin percentage (e.g., 50 = 50% markup on cost)');
            $table->decimal('minimum_price', 10, 4)->nullable()->comment('Minimum final price to ensure profitability');
            $table->string('currency', 3)->default('NGN');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure one active pricing per network per country
            $table->unique(['country', 'network'], 'unique_country_network');
            $table->index(['country', 'network', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_pricing');
    }
};
