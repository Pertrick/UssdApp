<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check which columns already exist
        $columns = Schema::getColumnListing('businesses');
        
        Schema::table('businesses', function (Blueprint $table) use ($columns) {
            // Billing method (prepaid or postpaid)
            if (!in_array('billing_method', $columns)) {
                $table->string('billing_method', 20)->default('postpaid')->after('billing_enabled');
            }
            
            // Postpaid-specific fields
            if (!in_array('credit_limit', $columns)) {
                $table->decimal('credit_limit', 15, 4)->nullable()->after('billing_method');
            }
            if (!in_array('payment_terms_days', $columns)) {
                $table->integer('payment_terms_days')->default(15)->after('credit_limit'); // Net 15, Net 30, etc.
            }
            if (!in_array('billing_cycle', $columns)) {
                $table->string('billing_cycle', 20)->default('monthly')->after('payment_terms_days'); // daily, weekly, monthly
            }
            
            // Billing change request fields
            if (!in_array('billing_change_request', $columns)) {
                $table->string('billing_change_request')->nullable()->after('billing_cycle'); // requested_method:prepaid/postpaid
            }
            if (!in_array('billing_change_reason', $columns)) {
                $table->text('billing_change_reason')->nullable()->after('billing_change_request');
            }
            if (!in_array('billing_change_requested_at', $columns)) {
                $table->timestamp('billing_change_requested_at')->nullable()->after('billing_change_reason');
            }
            
            // Postpaid account status
            if (!in_array('account_suspended', $columns)) {
                $table->boolean('account_suspended')->default(false)->after('billing_change_requested_at');
            }
            if (!in_array('suspension_reason', $columns)) {
                $table->text('suspension_reason')->nullable()->after('account_suspended');
            }
            if (!in_array('suspended_at', $columns)) {
                $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
            }
        });
        
        // Add indexes separately to avoid errors if they already exist
        // Check if indexes exist using raw SQL
        $indexes = DB::select("SHOW INDEXES FROM businesses WHERE Key_name IN ('businesses_billing_method_index', 'businesses_account_suspended_index', 'businesses_billing_change_request_index')");
        $existingIndexes = array_column($indexes, 'Key_name');
        
        Schema::table('businesses', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('businesses_billing_method_index', $existingIndexes)) {
                $table->index(['billing_method']);
            }
            if (!in_array('businesses_account_suspended_index', $existingIndexes)) {
                $table->index(['account_suspended']);
            }
            if (!in_array('businesses_billing_change_request_index', $existingIndexes)) {
                $table->index(['billing_change_request']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['billing_method']);
            $table->dropIndex(['account_suspended']);
            $table->dropIndex(['billing_change_request']);
            
            $table->dropColumn([
                'billing_method',
                'credit_limit',
                'payment_terms_days',
                'billing_cycle',
                'billing_change_request',
                'billing_change_reason',
                'billing_change_requested_at',
                'account_suspended',
                'suspension_reason',
                'suspended_at',
            ]);
        });
    }
};

