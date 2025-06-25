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
        Schema::create('ussd_flow_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('ussd_flows')->onDelete('cascade');
            $table->string('option_text'); // Text shown to user (e.g., "1. Check Balance")
            $table->string('option_value'); // Value user enters (e.g., "1")
            $table->foreignId('next_flow_id')->nullable()->constrained('ussd_flows')->onDelete('set null'); // Where to go next
            $table->string('action_type')->default('navigate'); // navigate, api_call, message, end_session, etc.
            $table->json('action_data')->nullable(); // Configuration for the action
            $table->boolean('requires_input')->default(false); // Does this option require additional input?
            $table->string('input_validation')->nullable(); // Validation rules for input (regex, length, etc.)
            $table->text('input_prompt')->nullable(); // What to ask user for input
            $table->integer('sort_order')->default(0); // For ordering options
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['flow_id', 'option_value']);
            $table->index(['flow_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ussd_flow_options');
    }
}; 