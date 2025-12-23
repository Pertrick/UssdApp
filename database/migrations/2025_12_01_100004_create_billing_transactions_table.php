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
        Schema::create('billing_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('ussd_session_id')->nullable()->constrained('ussd_sessions')->onDelete('set null');
            
            // Transaction identification
            $table->string('transaction_number')->unique()->index();
            $table->string('reference_number')->nullable();
            
            // Transaction details
            $table->string('type', 20); // charge, payment, refund, credit, adjustment
            $table->string('method', 20); // prepaid, postpaid
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3)->default('USD');
            
            // Transaction status
            $table->string('status', 20)->default('pending'); // pending, completed, failed, cancelled, refunded
            $table->text('description')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Balance tracking
            $table->decimal('balance_before', 15, 4)->nullable();
            $table->decimal('balance_after', 15, 4)->nullable();
            
            // Payment gateway information (if applicable)
            $table->string('gateway')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['business_id', 'type']);
            $table->index(['business_id', 'status']);
            $table->index(['invoice_id']);
            $table->index(['ussd_session_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_transactions');
    }
};

