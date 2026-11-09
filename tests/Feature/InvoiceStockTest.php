<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_invoice_decrements_product_stock(): void
    {
        $user = User::factory()->create();

        $supplier = Supplier::create([
            'name' => 'Fournisseur Test',
        ]);

        $product = Product::create([
            'supplier_id' => $supplier->id,
            'name' => 'Produit Test',
            'unit_price' => 100,
            'quantity' => 20,
            'low_stock_threshold' => 5,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'invoice_date' => '2026-07-18',
            'customer_name' => 'Client Test',
            'customer_phone' => '0600000000',
            'customer_email' => 'client@test.com',
            'tax' => 20,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $response->assertRedirect(route('invoices.show', $invoice));

        $this->assertEquals(17, $product->fresh()->quantity);
        $this->assertEquals(300, $invoice->subtotal);
        $this->assertEquals(360, $invoice->total);
    }

    public function test_invoice_status_can_be_marked_as_paid(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-0001',
            'invoice_date' => '2026-07-18',
            'customer_name' => 'Client Statut',
            'status' => 'draft',
            'subtotal' => 100,
            'tax' => 0,
            'total' => 100,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.status', $invoice), [
            'status' => 'paid',
        ]);

        $response->assertRedirect();
        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    public function test_invoice_can_apply_percentage_discount_and_tva(): void
    {
        $user = User::factory()->create();

        $supplier = Supplier::create([
            'name' => 'Fournisseur Test',
        ]);

        $product = Product::create([
            'supplier_id' => $supplier->id,
            'name' => 'Produit Reduction',
            'unit_price' => 200,
            'quantity' => 10,
            'low_stock_threshold' => 2,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'invoice_date' => '2026-07-18',
            'customer_name' => 'Client Reduction',
            'tax' => 30,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $invoice = Invoice::latest('id')->first();
        $response->assertRedirect(route('invoices.show', $invoice));

        $this->assertEquals(400, $invoice->subtotal);
        $this->assertEquals('percent', $invoice->discount_type);
        $this->assertEquals(10, $invoice->discount_value);
        $this->assertEquals(468, $invoice->total);
    }

    public function test_invoice_can_store_payment_method_and_custom_fields(): void
    {
        $user = User::factory()->create();

        $supplier = Supplier::create([
            'name' => 'Fournisseur Paiement',
        ]);

        $product = Product::create([
            'supplier_id' => $supplier->id,
            'name' => 'Produit Paiement',
            'unit_price' => 150,
            'quantity' => 20,
            'low_stock_threshold' => 3,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'invoice_date' => '2026-07-19',
            'customer_name' => 'Client Paiement Champ',
            'payment_method' => 'virement',
            'invoice_custom_fields' => [
                [
                    'label' => 'Reference BC',
                    'value' => 'BC-2026-77',
                    'show_on_invoice' => '1',
                ],
                [
                    'label' => 'Projet',
                    'value' => 'Chantier Atlas',
                    'show_on_invoice' => '0',
                ],
            ],
            'tax' => 20,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $invoice = Invoice::latest('id')->first();

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame('virement', $invoice->payment_method);
        $this->assertCount(2, $invoice->invoice_custom_fields);
        $this->assertSame('Reference BC', $invoice->invoice_custom_fields[0]['label']);
        $this->assertTrue($invoice->invoice_custom_fields[0]['show_on_invoice']);
        $this->assertFalse($invoice->invoice_custom_fields[1]['show_on_invoice']);
    }

    public function test_invoice_uses_default_tax_rate_from_settings_when_not_overridden(): void
    {
        $user = User::factory()->create();
        CompanySetting::current()->update(['default_tax_rate' => 20]);

        $supplier = Supplier::create([
            'name' => 'Fournisseur TVA',
        ]);

        $product = Product::create([
            'supplier_id' => $supplier->id,
            'name' => 'Produit TVA',
            'unit_price' => 100,
            'quantity' => 20,
            'low_stock_threshold' => 2,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'invoice_date' => '2026-07-19',
            'customer_name' => 'Client TVA',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $invoice = Invoice::latest('id')->first();

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertEquals(20.0, (float) $invoice->tax);
        $this->assertEquals(240.0, (float) $invoice->total);
    }

    public function test_invoice_partial_payment_updates_paid_and_due_amounts(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-PAY-0001',
            'invoice_date' => '2026-07-18',
            'customer_name' => 'Client Paiement',
            'status' => 'sent',
            'subtotal' => 100,
            'tax' => 0,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'total' => 100,
            'paid_amount' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.payments.store', $invoice), [
            'amount' => 20,
        ]);

        $response->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(20, $invoice->normalizedPaidAmount());
        $this->assertEquals(80, $invoice->amountDue());
        $this->assertEquals('pending', $invoice->status);

        $this->actingAs($user)->post(route('invoices.payments.store', $invoice), [
            'amount' => 80,
        ]);

        $invoice->refresh();
        $this->assertEquals(100, $invoice->normalizedPaidAmount());
        $this->assertEquals(0, $invoice->amountDue());
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_invoice_total_can_be_rendered_in_words_with_dirhams(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-WORDS-0001',
            'invoice_date' => '2026-07-19',
            'customer_name' => 'Client Montant',
            'status' => 'draft',
            'subtotal' => 1250,
            'tax' => 20,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'paid_amount' => 0,
            'total' => 1500,
        ]);

        $words = $invoice->totalInWords();

        $this->assertStringContainsString('dirhams', strtolower($words));
        $this->assertStringContainsString('mille', strtolower($words));
        $this->assertStringContainsString('cinq cents', strtolower($words));
    }

    public function test_invoice_can_be_created_with_manual_invoice_number(): void
    {
        $user = User::factory()->create();

        $supplier = Supplier::create([
            'name' => 'Fournisseur Numero',
        ]);

        $product = Product::create([
            'supplier_id' => $supplier->id,
            'name' => 'Produit Numero',
            'unit_price' => 100,
            'quantity' => 10,
            'low_stock_threshold' => 2,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'invoice_number' => 'FAC-MANUEL-2026-001',
            'invoice_date' => '2026-07-26',
            'customer_name' => 'Client Numero',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $invoice = Invoice::latest('id')->first();

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame('FAC-MANUEL-2026-001', $invoice->invoice_number);
    }
}
