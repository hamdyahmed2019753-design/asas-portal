<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('profit');         // profit|capital
            $table->unsignedInteger('sequence')->nullable();   // 1..payouts_count للربح، null لرأس المال
            $table->date('due_date');
            $table->decimal('amount', 14, 2)->nullable();      // يدوي للربح؛ = مبلغ المشاركة لرأس المال
            $table->string('status')->default('scheduled');    // scheduled|due|paid
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
