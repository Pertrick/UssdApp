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
        Schema::create('external_api_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ussd_id')->nullable()->constrained()->onDelete('cascade');
            
            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('custom'); // marketplace, custom, community
            $table->string('provider_name')->nullable(); // For marketplace APIs
            
            // API Configuration
            $table->string('endpoint_url');
            $table->string('method')->default('POST'); // GET, POST, PUT, DELETE
            $table->integer('timeout')->default(30); // seconds
            $table->integer('retry_attempts')->default(3);
            
            // Authentication
            $table->string('auth_type')->default('api_key'); // api_key, bearer_token, oauth, basic, none
            $table->json('auth_config')->nullable(); // Encrypted authentication details
            
            // Request Configuration
            $table->json('headers')->nullable();
            $table->json('request_mapping')->nullable(); // How to map USSD data to API request
            $table->json('request_template')->nullable(); // Template for request body
            
            // Response Configuration
            $table->json('response_mapping')->nullable(); // How to map API response to USSD flow
            $table->json('success_criteria')->nullable(); // How to determine if API call was successful
            $table->json('error_handling')->nullable(); // Error handling rules
            
            // Status and Validation
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false); // For marketplace APIs
            $table->timestamp('last_tested_at')->nullable();
            $table->string('test_status')->default('pending'); // pending, success, failed
            
            // Usage and Analytics
            $table->integer('total_calls')->default(0);
            $table->integer('successful_calls')->default(0);
            $table->integer('failed_calls')->default(0);
            $table->decimal('average_response_time', 8, 3)->nullable(); // milliseconds
            
            // Marketplace Specific
            $table->boolean('is_marketplace_template')->default(false);
            $table->string('marketplace_category')->nullable();
            $table->json('marketplace_metadata')->nullable(); // Pricing, features, etc.
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'category']);
            $table->index(['ussd_id', 'is_active']);
            $table->index(['category', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_api_configurations');
    }
};
