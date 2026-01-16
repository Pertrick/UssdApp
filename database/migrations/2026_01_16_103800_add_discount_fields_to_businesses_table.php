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
            // Discount can be percentage (0-100) or fixed amount
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('session_price')->comment('Percentage discount (0-100), null means no discount');
            $table->decimal('discount_amount', 10, 4)->nullable()->after('discount_percentage')->comment('Fixed amount discount, null means no discount');
            $table->enum('discount_type', ['percentage', 'fixed', 'none'])->default('none')->after('discount_amount')->comment('Type of discount applied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['discount_percentage', 'discount_amount', 'discount_type']);
        });
    }
};
