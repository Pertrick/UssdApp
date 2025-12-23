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
        Schema::create('flow_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ussd_id')->constrained()->onDelete('cascade');
            $table->string('key')->index(); // Configuration key (e.g., 'transaction_fee', 'supported_networks')
            $table->json('value'); // Configuration value (can be string, number, array, object)
            $table->text('description')->nullable(); // Human-readable description
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['ussd_id', 'key']);
            $table->unique(['ussd_id', 'key']); // Prevent duplicate keys per USSD
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flow_configs');
    }
};