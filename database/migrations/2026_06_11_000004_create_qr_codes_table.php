<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 16)->nullable()->unique();
            $table->string('type')->index();
            $table->jsonb('content');
            $table->text('destination_url')->nullable();
            $table->boolean('is_dynamic')->default(false);
            $table->string('status')->default('active');
            $table->boolean('admin_locked')->default(false);
            $table->boolean('frozen')->default(false);
            $table->jsonb('design_options')->nullable();
            $table->unsignedBigInteger('scan_count')->default(0);
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
