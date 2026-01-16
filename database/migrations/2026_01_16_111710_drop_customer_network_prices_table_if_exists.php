<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Drop customer_network_prices table as it's been replaced by network_pricing (dynamic markup model)
     */
    public function up(): void
    {
        // Drop table if it exists (may not exist if migration was never run)
        if (Schema::hasTable('customer_network_prices')) {
            Schema::dropIfExists('customer_network_prices');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate table structure if needed for rollback
        Schema::create('customer_network_prices', function (Blueprint $table) {
            $table->id();
            $table->string('country', 2)->default('NG');
            $table->string('network');
            $table->decimal('price_per_session', 10, 4);
            $table->string('currency', 3)->default('NGN');
            $table->date('effective_from')->default(now());
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country', 'network', 'is_active']);
            $table->index(['country', 'network', 'effective_from']);
        });
    }
};
