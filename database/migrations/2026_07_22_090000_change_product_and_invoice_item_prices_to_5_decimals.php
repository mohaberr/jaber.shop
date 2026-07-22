<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('unit_price', 12, 5)->default(0)->change();
        });

        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 12, 5)->change();
            $table->decimal('line_total', 12, 5)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('unit_price', 12, 2)->default(0)->change();
        });

        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 12, 2)->change();
            $table->decimal('line_total', 12, 2)->change();
        });
    }
};