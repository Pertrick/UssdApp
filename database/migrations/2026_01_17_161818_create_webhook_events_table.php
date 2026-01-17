<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     * 
     * Stores raw webhook events from gateway providers (e.g., AfricasTalking)
     * for auditing, debugging, and reconciliation purposes.
     */
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->default('session_end')->comment('Type of event: session_end, cost_update, etc.');
            $table->string('source')->default('africastalking')->comment('Source of webhook: africastalking, etc.');
            $table->string('session_id')->nullable()->comment('AfricasTalking session ID');
            $table->foreignId('ussd_session_id')->nullable()->constrained('ussd_sessions')->onDelete('set null')->comment('Reference to our ussd_sessions table');
            $table->json('payload')->comment('Raw webhook payload from gateway');
            $table->json('headers')->nullable()->comment('HTTP headers from webhook request');
            $table->string('ip_address')->nullable()->comment('IP address of webhook sender');
            $table->enum('processing_status', ['pending', 'processed', 'failed'])->default('pending')->comment('Status of event processing');
            $table->text('processing_error')->nullable()->comment('Error message if processing failed');
            $table->timestamp('processed_at')->nullable()->comment('When event was processed');
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index('event_type');
            $table->index('source');
            $table->index('session_id');
            $table->index('ussd_session_id');
            $table->index('processing_status');
            $table->index('created_at');
            $table->index(['source', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
