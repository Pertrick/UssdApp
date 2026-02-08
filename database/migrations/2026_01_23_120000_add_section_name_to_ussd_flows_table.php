<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ussd_flows', function (Blueprint $table) {
            $table->string('section_name', 100)->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('ussd_flows', function (Blueprint $table) {
            $table->dropColumn('section_name');
        });
    }
};
