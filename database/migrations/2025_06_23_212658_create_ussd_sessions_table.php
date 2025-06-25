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
        Schema::create('ussd_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ussd_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique(); // Unique session identifier
            $table->string('phone_number')->nullable(); // User's phone number (for simulation)
            $table->foreignId('current_flow_id')->nullable()->constrained('ussd_flows')->onDelete('set null');
            $table->text('user_input')->nullable(); // Current user input
            $table->json('session_data')->nullable(); // Store session variables (balance, PIN, etc.)
            $table->enum('status', ['active', 'completed', 'timeout', 'error'])->default('active');
            $table->integer('step_count')->default(0); // Number of interactions
            $table->timestamp('last_activity')->nullable(); // Last user interaction
            $table->timestamp('expires_at')->nullable(); // Session timeout
            $table->string('user_agent')->nullable(); // Browser/device info for simulation
            $table->string('ip_address')->nullable(); // IP address for monitoring
            $table->timestamps();
            
            // Indexes for performance and monitoring
            $table->index(['ussd_id', 'status']);
            $table->index(['session_id']);
            $table->index(['phone_number']);
            $table->index(['last_activity']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ussd_sessions');
    }
};
