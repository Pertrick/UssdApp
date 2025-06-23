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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('business_email')->unique();
            $table->string('phone');
            $table->string('state');
            $table->string('city');
            $table->text('address');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Registration Status
            $table->enum('registration_status', [
                'email_verification_pending',
                'cac_info_pending',
                'director_info_pending',
                'completed'
            ])->default('email_verification_pending');
            
            // CAC Information
            $table->string('cac_number')->nullable();
            $table->string('cac_document_path')->nullable();
            $table->date('registration_date')->nullable();
            $table->enum('business_type', ['sole_proprietorship', 'partnership', 'limited_liability'])->nullable();
            
            // Director Information
            $table->string('director_name')->nullable();
            $table->string('director_email')->nullable();
            $table->string('director_phone')->nullable();
            $table->enum('director_id_type', ['national_id', 'drivers_license', 'international_passport'])->nullable();
            $table->string('director_id_number')->nullable();
            $table->string('director_id_path')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
