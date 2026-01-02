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
        Schema::create('gateway_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Configuration name (e.g., "AfricasTalking Production", "AfricasTalking Sandbox")');
            $table->string('gateway_provider')->comment('Gateway provider: africastalking, hubtel, twilio, etc.');
            $table->text('api_key')->comment('Encrypted API key');
            $table->text('username')->comment('Encrypted username');
            $table->string('environment')->default('production')->comment('production, sandbox, testing');
            $table->boolean('is_default')->default(false)->comment('Default configuration for new USSDs');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['gateway_provider', 'environment']);
            $table->index('is_default');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateway_configurations');
    }
};
