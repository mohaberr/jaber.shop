<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->json('invoice_field_visibility')->nullable()->after('legal_mentions');
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->json('custom_fields')->nullable()->after('address');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->json('customer_custom_fields')->nullable()->after('customer_address');
            $table->string('payment_method')->nullable()->after('customer_custom_fields');
            $table->json('invoice_custom_fields')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['customer_custom_fields', 'payment_method', 'invoice_custom_fields']);
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn('custom_fields');
        });

        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropColumn(['invoice_field_visibility']);
        });
    }
};
