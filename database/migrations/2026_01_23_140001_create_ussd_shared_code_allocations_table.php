<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ussd_shared_code_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_ussd_id')->constrained('ussds')->onDelete('cascade');
            $table->string('option_value', 20); // e.g. "1", "2"
            $table->foreignId('target_ussd_id')->constrained('ussds')->onDelete('cascade');
            $table->string('label', 100); // e.g. "MCD", "PlanetF"
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['gateway_ussd_id', 'option_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ussd_shared_code_allocations');
    }
};
