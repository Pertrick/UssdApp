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
        Schema::create('billing_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            
            // Request details
            $table->string('current_method', 20); // Current billing method
            $table->string('requested_method', 20); // Requested billing method
            $table->text('reason')->nullable();
            
            // Request status
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['business_id', 'status']);
            $table->index(['status']);
            $table->index(['requested_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_change_requests');
    }
};

