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
            $table->unsignedBigInteger('environment_id')->nullable()->after('is_active');
            $table->foreign('environment_id')->references('id')->on('environments')->onDelete('set null');
        });

        Schema::table('ussds', function (Blueprint $table) {
            $table->dropColumn('environment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ussds', function (Blueprint $table) {
            $table->enum('environment', ['simulation', 'testing', 'production'])
                  ->default('simulation')
                  ->after('is_active');
        });

        Schema::table('ussds', function (Blueprint $table) {
            $table->dropForeign(['environment_id']);
            $table->dropColumn('environment_id');
        });
    }
};
