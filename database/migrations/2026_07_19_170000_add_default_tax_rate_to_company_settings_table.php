<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('company_settings', 'default_tax_rate')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table): void {
            $table->decimal('default_tax_rate', 5, 2)->default(0)->after('tax_number');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('company_settings', 'default_tax_rate')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropColumn('default_tax_rate');
        });
    }
};