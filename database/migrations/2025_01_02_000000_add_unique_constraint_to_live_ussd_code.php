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
        if (!Schema::hasColumn('ussds', 'live_ussd_code')) {
            return;
        }
        
        $indexExists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'ussds' 
            AND index_name = 'ussds_live_ussd_code_unique'
        ");
        
        // Only create index if it doesn't exist
        if (empty($indexExists) || $indexExists[0]->count == 0) {
            Schema::table('ussds', function (Blueprint $table) {
                $table->unique('live_ussd_code', 'ussds_live_ussd_code_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ussds', function (Blueprint $table) {
            $table->dropUnique('ussds_live_ussd_code_unique');
        });
    }
};
