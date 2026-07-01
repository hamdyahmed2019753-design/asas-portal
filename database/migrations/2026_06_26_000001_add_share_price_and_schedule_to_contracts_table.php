<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Fixed price per share; subscription amount = shares × share_price.
            $table->decimal('share_price', 15, 2)->nullable()->after('max_amount');
            // Admin-defined absolute profit-distribution dates (shared by all
            // investors in the contract). Null → fall back to even spacing.
            $table->json('payout_schedule')->nullable()->after('payouts_count');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['share_price', 'payout_schedule']);
        });
    }
};
