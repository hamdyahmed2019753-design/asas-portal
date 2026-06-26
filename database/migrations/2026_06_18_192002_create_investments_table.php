<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('status')->default('pending');     // pending|approved|rejected
            $table->date('start_date')->nullable();           // يُضبط عند الاعتماد
            $table->date('end_date')->nullable();             // start_date + duration_months
            $table->dateTime('approved_at')->nullable();      // وقت الاعتماد
            $table->text('rejection_reason')->nullable();     // سبب الرفض
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
