<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for created_at — every resource table defaults to sorting by it and
 * exposes a created_at date-range filter.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = ['contracts', 'investments', 'users', 'news_updates'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropIndex(['created_at']);
            });
        }
    }
};
