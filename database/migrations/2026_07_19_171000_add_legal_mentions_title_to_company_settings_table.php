<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('company_settings', 'legal_mentions_title')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table): void {
            $table->string('legal_mentions_title')->default('Mentions legales')->after('default_tax_rate');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('company_settings', 'legal_mentions_title')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropColumn('legal_mentions_title');
        });
    }
};