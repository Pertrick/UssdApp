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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('ussd_session_id')->nullable()->constrained('ussd_sessions')->onDelete('set null');
            
            // Item details
            $table->string('description');
            $table->text('details')->nullable(); // Additional item details
            
            // Quantity and pricing
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4); // (unit_price * quantity) - discount + tax
            
            // Item metadata
            $table->string('item_type')->default('session'); // session, adjustment, credit, refund
            $table->json('metadata')->nullable(); // Additional flexible data
            
            $table->timestamps();
            
            // Indexes
            $table->index(['invoice_id']);
            $table->index(['ussd_session_id']);
            $table->index(['item_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};

