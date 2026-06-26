<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds indexes for columns used heavily in filters, status lookups and date
 * ranges. Foreign keys are already indexed by ->constrained().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('payouts', function (Blueprint $table) {
            $table->index('status');
            $table->index('type');
            $table->index('due_date');
        });

        Schema::table('news_updates', function (Blueprint $table) {
            $table->index('is_published');
            $table->index('published_at');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('payouts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
            $table->dropIndex(['due_date']);
        });

        Schema::table('news_updates', function (Blueprint $table) {
            $table->dropIndex(['is_published']);
            $table->dropIndex(['published_at']);
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['event']);
        });
    }
};
