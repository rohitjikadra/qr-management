<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_complimentary')->default(false)->after('status');
            $table->text('admin_note')->nullable()->after('is_complimentary');
            $table->foreignId('granted_by')->nullable()->after('admin_note')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('granted_by');
            $table->dropColumn(['is_complimentary', 'admin_note']);
        });
    }
};
