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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 4);
            $table->string('currency', 3)->default('USD');
            $table->string('gateway'); // stripe, paypal, flutterwave, paystack, manual
            $table->string('status')->default('pending'); // pending, completed, failed, cancelled
            $table->string('reference')->unique(); // Unique payment reference
            $table->json('metadata')->nullable(); // Additional payment data
            $table->json('gateway_response')->nullable(); // Gateway response data
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['business_id', 'status']);
            $table->index(['reference']);
            $table->index(['gateway', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
