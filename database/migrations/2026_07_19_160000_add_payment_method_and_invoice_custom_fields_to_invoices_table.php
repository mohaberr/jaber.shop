<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (!Schema::hasColumn('invoices', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('customer_custom_fields');
            }

            if (!Schema::hasColumn('invoices', 'invoice_custom_fields')) {
                $table->json('invoice_custom_fields')->nullable()->after('payment_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $columnsToDrop = [];

            if (Schema::hasColumn('invoices', 'invoice_custom_fields')) {
                $columnsToDrop[] = 'invoice_custom_fields';
            }

            if (Schema::hasColumn('invoices', 'payment_method')) {
                $columnsToDrop[] = 'payment_method';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};