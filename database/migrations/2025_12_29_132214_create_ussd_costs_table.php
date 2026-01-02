<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ussd_costs', function (Blueprint $table) {
            $table->id();
            $table->string('country', 2)->default('NG');
            $table->string('network');
            $table->unsignedBigInteger('cost_per_session');
            $table->string('currency', 3)->default('NGN');
            $table->date('effective_from');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country', 'network', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ussd_costs');
    }
};
