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
        Schema::create('ussd_session_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ussd_sessions')->onDelete('cascade');
            $table->foreignId('ussd_id')->constrained()->onDelete('cascade');
            $table->foreignId('flow_id')->nullable()->constrained('ussd_flows')->onDelete('set null');
            $table->foreignId('flow_option_id')->nullable()->constrained('ussd_flow_options')->onDelete('set null');
            $table->string('action_type'); // user_input, menu_display, navigation, error, timeout
            $table->text('input_data')->nullable(); // What user entered
            $table->text('output_data')->nullable(); // What was shown to user
            $table->string('response_time')->nullable(); // How long it took to respond (in milliseconds)
            $table->enum('status', ['success', 'error', 'timeout'])->default('success');
            $table->text('error_message')->nullable(); // Error details if any
            $table->json('metadata')->nullable(); // Additional data (browser info, etc.)
            $table->timestamp('action_timestamp'); // When this action occurred
            $table->timestamps();
            
            // Indexes for monitoring and analytics
            $table->index(['ussd_id', 'action_timestamp']);
            $table->index(['session_id', 'action_timestamp']);
            $table->index(['action_type', 'action_timestamp']);
            $table->index(['status', 'action_timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ussd_session_logs');
    }
};
