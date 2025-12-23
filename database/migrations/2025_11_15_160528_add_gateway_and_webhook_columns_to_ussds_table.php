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
        Schema::table('ussds', function (Blueprint $table) {
            // Gateway configuration
            // Check if environment_id exists, if not add after is_active
            $afterColumn = Schema::hasColumn('ussds', 'environment_id') ? 'environment_id' : 'is_active';
            $table->string('gateway_provider')->nullable()->after($afterColumn)->comment('Gateway provider: africastalking, hubtel, twilio, etc.');
            $table->text('gateway_credentials')->nullable()->after('gateway_provider')->comment('Encrypted JSON credentials for gateway');
            
            // Webhook configuration
            $table->string('webhook_url', 500)->nullable()->after('gateway_credentials')->comment('Webhook URL for receiving USSD requests');
            $table->string('callback_url', 500)->nullable()->after('webhook_url')->comment('Callback URL (optional, separate from webhook)');
            
            // USSD codes
            $table->string('live_ussd_code', 50)->nullable()->after('callback_url')->comment('Production USSD code from gateway provider');
            $table->string('testing_ussd_code', 50)->nullable()->after('live_ussd_code')->comment('Testing USSD code');
            
            // Monetization fields (if not already exists)
            if (!Schema::hasColumn('ussds', 'monetization_enabled')) {
                $table->boolean('monetization_enabled')->default(false)->after('testing_ussd_code');
            }
            if (!Schema::hasColumn('ussds', 'pricing_model')) {
                $table->string('pricing_model')->nullable()->after('monetization_enabled')->comment('per_session, per_transaction, subscription');
            }
            if (!Schema::hasColumn('ussds', 'session_price')) {
                $table->decimal('session_price', 10, 4)->nullable()->after('pricing_model')->comment('Price per session');
            }
            if (!Schema::hasColumn('ussds', 'transaction_price')) {
                $table->decimal('transaction_price', 10, 4)->nullable()->after('session_price')->comment('Price per transaction');
            }
            if (!Schema::hasColumn('ussds', 'subscription_price')) {
                $table->decimal('subscription_price', 10, 4)->nullable()->after('transaction_price')->comment('Monthly subscription price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ussds', function (Blueprint $table) {
            $table->dropColumn([
                'gateway_provider',
                'gateway_credentials',
                'webhook_url',
                'callback_url',
                'live_ussd_code',
                'testing_ussd_code',
                'monetization_enabled',
                'pricing_model',
                'session_price',
                'transaction_price',
                'subscription_price',
            ]);
        });
    }
};
