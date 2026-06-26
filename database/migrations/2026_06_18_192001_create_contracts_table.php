<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('title');                              // اسم العقد
            $table->string('activity_type');                      // نوع النشاط المالي
            $table->decimal('expected_return', 5, 2)->nullable(); // نسبة العائد المتوقعة % (عرض فقط)
            $table->decimal('target_amount', 14, 2);              // النصاب المستهدف
            $table->decimal('min_amount', 14, 2);                 // أقل مشاركة
            $table->decimal('max_amount', 14, 2)->nullable();     // أقصى مشاركة
            $table->unsignedInteger('duration_months');           // مدة العقد بالشهور
            $table->unsignedInteger('payouts_count')->default(4); // عدد توزيعات الربح
            $table->string('status')->default('upcoming');        // upcoming|open|running|closed|finished
            $table->dateTime('opens_at')->nullable();             // للعدّاد التنازلي
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
