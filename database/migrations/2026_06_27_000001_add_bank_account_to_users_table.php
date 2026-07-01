<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Investor's payout bank account (profit transfers + withdrawals).
            $table->string('bank_name')->nullable()->after('country');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_iban', 34)->nullable()->after('bank_account_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_name', 'bank_iban']);
        });
    }
};
