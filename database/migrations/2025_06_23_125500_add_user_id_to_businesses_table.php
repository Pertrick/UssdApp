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
        Schema::table('businesses', function (Blueprint $table) {
            // Add user_id foreign key
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
            
            // Add business_email column
            $table->string('business_email')->after('business_name');
            
            // Add is_primary column
            $table->boolean('is_primary')->default(false)->after('registration_status');
            
            // Remove authentication fields from businesses table
            $table->dropColumn(['password', 'email_verified_at', 'remember_token']);
            
            // Rename 'name' to 'business_name' for clarity
            $table->renameColumn('name', 'business_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Remove foreign key
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'is_primary']);
            
            // Add back authentication fields
            $table->string('business_email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            
            // Rename back
            $table->renameColumn('business_name', 'name');
        });
    }
}; 