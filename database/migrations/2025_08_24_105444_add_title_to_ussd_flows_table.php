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
        Schema::table('ussd_flows', function (Blueprint $table) {
            $table->string('title')->nullable()->after('name'); // Flow title/header (e.g., "Report type of fire")
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ussd_flows', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
