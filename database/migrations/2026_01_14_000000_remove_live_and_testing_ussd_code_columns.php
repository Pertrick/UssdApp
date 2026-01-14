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
            // Drop the live_ussd_code and testing_ussd_code columns
            // We now use only the pattern field for all environments
            if (Schema::hasColumn('ussds', 'live_ussd_code')) {
                $table->dropColumn('live_ussd_code');
            }
            if (Schema::hasColumn('ussds', 'testing_ussd_code')) {
                $table->dropColumn('testing_ussd_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ussds', function (Blueprint $table) {
            // Re-add the columns if needed for rollback
            $table->string('live_ussd_code', 50)->nullable()->after('callback_url');
            $table->string('testing_ussd_code', 50)->nullable()->after('live_ussd_code');
        });
    }
};
