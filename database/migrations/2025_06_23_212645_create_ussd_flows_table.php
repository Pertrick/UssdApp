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
        Schema::create('ussd_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ussd_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Flow name (e.g., "Main Menu", "Balance Check")
            $table->text('description')->nullable(); // Flow description
            $table->text('menu_text'); // The actual text shown to user
            $table->boolean('is_root')->default(false); // Is this the starting flow?
            $table->foreignId('parent_flow_id')->nullable()->constrained('ussd_flows')->onDelete('cascade'); // For nested flows
            $table->integer('sort_order')->default(0); // For ordering flows
            $table->json('flow_config')->nullable(); // Additional configuration (timeout, validation rules, etc.)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['ussd_id', 'is_root']);
            $table->index(['ussd_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ussd_flows');
    }
};
