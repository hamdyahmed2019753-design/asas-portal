<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only ledger for the investor cash wallet. Balance is the sum of
        // credits minus debits — never stored, always derived (auditable).
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('direction'); // credit | debit
            $table->decimal('amount', 15, 2);
            $table->string('reason'); // capital_return | withdrawal | withdrawal_refund | reinvestment | reinvestment_refund
            $table->nullableMorphs('reference'); // Payout / Withdrawal / Investment
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
