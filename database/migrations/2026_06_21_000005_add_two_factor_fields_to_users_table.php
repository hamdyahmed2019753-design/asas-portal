<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Architecture only — 2FA is "قريبًا" (not implemented in this feature).
            $table->boolean('two_factor_enabled')->default(false)->after('onboarding_completed_at');
            $table->text('two_factor_backup_codes')->nullable()->after('two_factor_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_enabled', 'two_factor_backup_codes']);
        });
    }
};
