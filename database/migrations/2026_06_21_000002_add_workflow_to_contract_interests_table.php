<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_interests', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_interests', 'notes')) {
                $table->text('notes')->nullable()->after('contract_id');
            }
            if (! Schema::hasColumn('contract_interests', 'status')) {
                $table->string('status')->default('pending')->after('notes');
            }
            if (! Schema::hasColumn('contract_interests', 'contacted_at')) {
                $table->timestamp('contacted_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('contract_interests', 'converted_at')) {
                $table->timestamp('converted_at')->nullable()->after('contacted_at');
            }
        });

        // Drop the one-row-per-contract rule so a rejected interest can be
        // re-expressed; duplicate protection for active (pending/contacted)
        // interests is enforced in the service. On MySQL the composite unique
        // is the only index backing the user_id FK, so add a standalone index
        // first, then drop the unique.
        Schema::table('contract_interests', function (Blueprint $table) {
            $table->index('user_id', 'contract_interests_user_id_index');
            $table->dropUnique('contract_interests_user_id_contract_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('contract_interests', function (Blueprint $table) {
            $table->unique(['user_id', 'contract_id']);
            $table->dropIndex('contract_interests_user_id_index');
            $table->dropColumn(['notes', 'status', 'contacted_at', 'converted_at']);
        });
    }
};
