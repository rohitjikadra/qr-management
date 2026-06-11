<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('scans')->default(0);
            $table->string('top_country', 2)->nullable();
            $table->string('top_device', 20)->nullable();
            $table->timestamps();

            $table->unique(['qr_code_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_daily_stats');
    }
};
