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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            
            // Invoice identification
            $table->string('invoice_number')->unique()->index(); // INV-2025-001, etc.
            $table->string('reference_number')->nullable()->unique(); // External reference
            
            // Invoice period (for postpaid billing cycles)
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            
            // Financial details
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->decimal('paid_amount', 15, 4)->default(0);
            $table->decimal('balance_due', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            
            // Invoice status
            $table->string('status', 20)->default('draft'); // draft, sent, paid, partially_paid, overdue, cancelled, refunded
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            
            // Payment information
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('payment_notes')->nullable();
            
            // Additional information
            $table->text('notes')->nullable();
            $table->text('terms')->nullable(); // Payment terms text
            $table->json('metadata')->nullable(); // Additional flexible data
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->integer('reminder_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['business_id', 'status']);
            $table->index(['status', 'due_date']);
            $table->index(['issue_date']);
            $table->index(['due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

