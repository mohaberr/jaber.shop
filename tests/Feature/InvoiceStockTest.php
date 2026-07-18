<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
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
}
