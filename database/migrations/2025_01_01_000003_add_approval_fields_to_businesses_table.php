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
            $table->text('approval_notes')->nullable()->after('rejection_reason');
            $table->text('suspension_reason')->nullable()->after('approval_notes');
            $table->timestamp('reviewed_at')->nullable()->after('suspension_reason');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['approval_notes', 'suspension_reason', 'reviewed_at', 'reviewed_by']);
        });
    }
};
