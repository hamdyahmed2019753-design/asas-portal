<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            // How the subscription was funded: an external bank transfer, or the
            // investor's in-app cash wallet (reinvestment).
            $table->string('payment_method')->default('bank_transfer')->after('payment_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
