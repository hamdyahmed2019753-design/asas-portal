<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->unsignedInteger('shares')->nullable()->after('amount');
            $table->string('receipt_path')->nullable()->after('shares');       // investor's transfer receipt
            $table->string('payment_proof_path')->nullable()->after('receipt_path'); // admin's payment proof
            $table->timestamp('payment_confirmed_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn(['shares', 'receipt_path', 'payment_proof_path', 'payment_confirmed_at']);
        });
    }
};
