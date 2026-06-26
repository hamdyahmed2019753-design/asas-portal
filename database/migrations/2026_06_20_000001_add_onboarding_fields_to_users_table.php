<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Step 1 — profile completion.
            $table->string('city')->nullable()->after('kyc_status');
            $table->string('country')->nullable()->after('city');

            // Step 2 — uploaded onboarding documents (private storage paths).
            $table->string('identity_document_path')->nullable()->after('country');
            $table->string('iban_document_path')->nullable()->after('identity_document_path');
            $table->string('address_document_path')->nullable()->after('iban_document_path');

            // Step 3 / completion timestamps.
            $table->timestamp('terms_accepted_at')->nullable()->after('address_document_path');
            $table->timestamp('onboarding_completed_at')->nullable()->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'country',
                'identity_document_path',
                'iban_document_path',
                'address_document_path',
                'terms_accepted_at',
                'onboarding_completed_at',
            ]);
        });
    }
};
