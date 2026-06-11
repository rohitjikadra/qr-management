<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_scan_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('browser', 50)->nullable();
            $table->text('referrer')->nullable();
            $table->string('ip_hash', 64);
            $table->timestamp('scanned_at')->index();

            $table->index(['qr_code_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_scan_events');
    }
};
