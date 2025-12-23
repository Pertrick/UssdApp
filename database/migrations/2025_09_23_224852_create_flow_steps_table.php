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
        Schema::create('flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ussd_id')->constrained()->onDelete('cascade');
            $table->string('step_id')->index(); // Unique identifier for this step (e.g., 'select_network', 'fetch_bundles')
            $table->string('type'); // menu, api_call, dynamic_menu, input, condition, message
            $table->json('data')->nullable(); // Step-specific configuration data
            $table->string('next_step')->nullable(); // Next step to execute
            $table->json('conditions')->nullable(); // Conditional logic for step execution
            $table->integer('sort_order')->default(0); // Order of execution
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['ussd_id', 'step_id']);
            $table->index(['ussd_id', 'type']);
            $table->index(['ussd_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flow_steps');
    }
};