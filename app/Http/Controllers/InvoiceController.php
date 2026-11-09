<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index()
    {
        return view('invoices.index', [
            'invoices' => Invoice::latest()->paginate(12),
            'statusOptions' => Invoice::STATUS_LABELS,
        ]);
    }

    public function create()
    {
        $company = CompanySetting::current();

        return view('invoices.create', [
            'products' => Product::orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(),
            'paymentMethodOptions' => Invoice::paymentMethodOptions(),
            'invoice' => new Invoice([
                'invoice_date' => now()->toDateString(),
                'tax' => (float) ($company->default_tax_rate ?? 0),
                'discount_type' => 'fixed',
                'discount_value' => 0,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateInvoice($request);
        $invoice = null;
        $defaultTaxRate = (float) (CompanySetting::current()->default_tax_rate ?? 0);

        DB::transaction(function () use (&$invoice, $data, $defaultTaxRate): void {
            $clientPayload = $this->resolveClientPayload($data);
            $taxRate = array_key_exists('tax', $data) ? (float) $data['tax'] : $defaultTaxRate;
            $invoiceNumber = $this->resolveInvoiceNumber($data);

            $invoice = Invoice::create([
                'client_id' => $clientPayload['client_id'],
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'customer_name' => $clientPayload['customer_name'],
                'customer_phone' => $clientPayload['customer_phone'],
                'customer_email' => $clientPayload['customer_email'],
                'customer_address' => $clientPayload['customer_address'],
                'customer_custom_fields' => $clientPayload['customer_custom_fields'],
                'payment_method' => $this->resolvePaymentMethod($data),
                'invoice_custom_fields' => $this->normalizeInvoiceCustomFields($data['invoice_custom_fields'] ?? []),
                'notes' => $data['notes'] ?? null,
                'tax' => $taxRate,
                'discount_type' => $data['discount_type'] ?? 'fixed',
                'discount_value' => $data['discount_value'] ?? 0,
                'paid_amount' => 0,
                'status' => 'draft',
            ]);

            $totals = $this->applyItemsAndStock($invoice, $data['items'], $taxRate, $data['discount_type'] ?? 'fixed', (float) ($data['discount_value'] ?? 0));
            $invoice->update($totals + [
                'tax' => $taxRate,
                'discount_type' => $data['discount_type'] ?? 'fixed',
                'discount_value' => $data['discount_value'] ?? 0,
            ]);
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Facture creee et stock mis a jour.');
    }

    public function show(Invoice $invoice)
    {
        return view('invoices.show', [
            'invoice' => $invoice->load('items.product'),
            'statusOptions' => Invoice::STATUS_LABELS,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        return view('invoices.edit', [
            'invoice' => $invoice->load('items.product'),
            'products' => Product::orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(),
            'paymentMethodOptions' => Invoice::paymentMethodOptions(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $this->validateInvoice($request, $invoice->id);

        DB::transaction(function () use ($invoice, $data): void {
            $clientPayload = $this->resolveClientPayload($data, $invoice->client_id);
            $invoiceNumber = $this->resolveInvoiceNumber($data, $invoice);

            $invoice->load('items');
            $this->restoreStockFromInvoice($invoice, 'Correction apres edition de facture');
            $invoice->items()->delete();

            $invoice->update([
                'client_id' => $clientPayload['client_id'],
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'customer_name' => $clientPayload['customer_name'],
                'customer_phone' => $clientPayload['customer_phone'],
                'customer_email' => $clientPayload['customer_email'],
                'customer_address' => $clientPayload['customer_address'],
                'customer_custom_fields' => $clientPayload['customer_custom_fields'],
                'payment_method' => $this->resolvePaymentMethod($data),
                'invoice_custom_fields' => $this->normalizeInvoiceCustomFields($data['invoice_custom_fields'] ?? []),
                'notes' => $data['notes'] ?? null,
                'tax' => $data['tax'] ?? 0,
                'discount_type' => $data['discount_type'] ?? 'fixed',
                'discount_value' => $data['discount_value'] ?? 0,
            ]);

            $totals = $this->applyItemsAndStock($invoice, $data['items'], (float) ($data['tax'] ?? 0), $data['discount_type'] ?? 'fixed', (float) ($data['discount_value'] ?? 0));

            $invoice->update([
                'total' => $totals['total'],
                'subtotal' => $totals['subtotal'],
                'paid_amount' => min((float) $invoice->paid_amount, $totals['total']),
            ]);

            if ((float) $invoice->fresh()->paid_amount >= $invoice->fresh()->computedTotal() && $invoice->status !== Invoice::STATUS_CANCELLED) {
                $invoice->update(['status' => Invoice::STATUS_PAID]);
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Facture modifiee et stock recalcule.');
    }

    public function destroy(Invoice $invoice)
    {
        DB::transaction(function () use ($invoice): void {
            $invoice->load('items');
            $this->restoreStockFromInvoice($invoice, 'Retour stock apres suppression facture');
            $invoice->delete();
        });

        return redirect()->route('invoices.index')->with('success', 'Facture supprimee et stock restaure.');
    }

    public function updateStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', Invoice::allowedStatuses())],
        ]);

        $invoice->update(['status' => $validated['status']]);

        return back()->with('success', 'Statut facture mis a jour: ' . $invoice->statusLabel() . '.');
    }

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === Invoice::STATUS_CANCELLED) {
            return back()->with('error', 'Impossible d\'enregistrer un paiement sur une facture annulee.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $nextPaidAmount = min($invoice->computedTotal(), $invoice->normalizedPaidAmount() + (float) $validated['amount']);
        $nextStatus = $invoice->status;

        if ($nextPaidAmount >= $invoice->computedTotal()) {
            $nextStatus = Invoice::STATUS_PAID;
        } elseif ($nextPaidAmount > 0 && $invoice->status !== Invoice::STATUS_PAID) {
            $nextStatus = Invoice::STATUS_PENDING;
        }

        $invoice->update([
            'paid_amount' => $nextPaidAmount,
            'status' => $nextStatus,
        ]);

        return back()->with('success', 'Paiement enregistre. Montant paye: ' . number_format($invoice->normalizedPaidAmount(), 2) . ', reste a payer: ' . number_format($invoice->amountDue(), 2) . '.');
    }

    public function sendEmail(Invoice $invoice): RedirectResponse
    {
        $invoice->load('items.product');

        if (!$invoice->customer_email) {
            return back()->with('error', 'Ajoute un email client avant envoi.');
        }

        $pdfBinary = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->output();
        Mail::to($invoice->customer_email)->send(new InvoiceMail($invoice, $pdfBinary));

        $invoice->update(['status' => 'sent']);

        return back()->with('success', 'Facture envoyee par email.');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load('items.product');

        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
            ->download($invoice->pdfFileName());
    }

    public function publicPdf(Request $request, Invoice $invoice)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $invoice->load('items.product');

        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
            ->download($invoice->pdfFileName());
    }

    public function sendWhatsapp(Invoice $invoice): RedirectResponse
    {
        if (!$invoice->customer_phone) {
            return back()->with('error', 'Ajoute un numero client avant WhatsApp.');
        }

        $phone = preg_replace('/\D+/', '', $invoice->customer_phone);
        $amount = number_format($invoice->computedTotal(), 2, '.', '');
        $pdfUrl = URL::temporarySignedRoute(
            'invoices.public-pdf',
            now()->addDays(7),
            ['invoice' => $invoice->id]
        );

        $message = rawurlencode(
            'Bonjour ' . $invoice->customer_name . ', voici votre facture ' .
            $invoice->invoice_number . ' de montant ' . $amount . '. ' .
            'Telechargez votre PDF ici: ' . $pdfUrl . ' Merci.'
        );

        return redirect('https://wa.me/' . $phone . '?text=' . $message);
    }

    private function validateInvoice(Request $request, ?int $invoiceId = null): array
    {
        return $request->validate(
            [
                'invoice_number' => ['nullable', 'string', 'max:255', Rule::unique('invoices', 'invoice_number')->ignore($invoiceId)],
                'invoice_date' => ['required', 'date'],
                'due_date' => ['nullable', 'date'],
                'client_id' => ['nullable', 'exists:clients,id'],
                'customer_name' => ['nullable', 'required_without_all:client_id,new_client_name', 'string', 'max:255'],
                'customer_phone' => ['nullable', 'string', 'max:255'],
                'customer_email' => ['nullable', 'email', 'max:255'],
                'customer_address' => ['nullable', 'string', 'max:255'],
                'customer_custom_fields' => ['nullable', 'array'],
                'customer_custom_fields.*.label' => ['nullable', 'string', 'max:100'],
                'customer_custom_fields.*.value' => ['nullable', 'string', 'max:255'],
                'customer_custom_fields.*.show_on_invoice' => ['nullable', 'boolean'],
                'payment_method' => ['nullable', 'string', 'in:' . implode(',', array_keys(Invoice::paymentMethodOptions()))],
                'custom_payment_method' => ['nullable', 'string', 'max:255'],
                'invoice_custom_fields' => ['nullable', 'array'],
                'invoice_custom_fields.*.label' => ['nullable', 'string', 'max:100'],
                'invoice_custom_fields.*.value' => ['nullable', 'string', 'max:255'],
                'invoice_custom_fields.*.show_on_invoice' => ['nullable', 'boolean'],
                'tax' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'discount_type' => ['nullable', 'in:fixed,percent'],
                'discount_value' => ['nullable', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string'],
                'new_client_name' => ['nullable', 'string', 'max:255'],
                'new_client_phone' => ['nullable', 'string', 'max:255'],
                'new_client_email' => ['nullable', 'email', 'max:255'],
                'new_client_address' => ['nullable', 'string', 'max:255'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.product_id' => ['required', 'exists:products,id'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
            ],
            [
                'invoice_number.unique' => 'Le numero de facture saisi existe deja. Veuillez choisir un autre numero.',
            ]
        );
    }

    private function resolveInvoiceNumber(array $data, ?Invoice $invoice = null): string
    {
        $manualInvoiceNumber = trim((string) ($data['invoice_number'] ?? ''));

        if ($manualInvoiceNumber !== '') {
            return $manualInvoiceNumber;
        }

        return $invoice?->invoice_number ?? $this->nextInvoiceNumber();
    }

    private function resolvePaymentMethod(array $data): ?string
    {
        $paymentMethod = $data['payment_method'] ?? null;

        if ($paymentMethod === 'autre') {
            $customPaymentMethod = trim((string) ($data['custom_payment_method'] ?? ''));

            return $customPaymentMethod !== '' ? $customPaymentMethod : null;
        }

        return $paymentMethod ?: null;
    }

    private function normalizeInvoiceCustomFields(array $fields): array
    {
        return collect($fields)
            ->map(function ($field): array {
                return [
                    'label' => trim((string) ($field['label'] ?? '')),
                    'value' => trim((string) ($field['value'] ?? '')),
                    'show_on_invoice' => !empty($field['show_on_invoice']),
                ];
            })
            ->filter(fn (array $field): bool => $field['label'] !== '' || $field['value'] !== '')
            ->values()
            ->all();
    }

    private function resolveClientPayload(array $data, ?int $fallbackClientId = null): array
    {
        if (!empty($data['new_client_name'])) {
            $client = Client::create([
                'name' => $data['new_client_name'],
                'phone' => $data['new_client_phone'] ?? null,
                'email' => $data['new_client_email'] ?? null,
                'address' => $data['new_client_address'] ?? null,
            ]);

            return [
                'client_id' => $client->id,
                'customer_name' => $client->name,
                'customer_phone' => $client->phone,
                'customer_email' => $client->email,
                'customer_address' => $client->address,
                'customer_custom_fields' => $client->custom_fields ?? [],
            ];
        }

        $clientId = $data['client_id'] ?? $fallbackClientId;
        if ($clientId) {
            $client = Client::findOrFail($clientId);

            return [
                'client_id' => $client->id,
                'customer_name' => $client->name,
                'customer_phone' => $client->phone,
                'customer_email' => $client->email,
                'customer_address' => $client->address,
                'customer_custom_fields' => $client->custom_fields ?? [],
            ];
        }

        return [
            'client_id' => null,
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'customer_address' => $data['customer_address'] ?? null,
            'customer_custom_fields' => collect($data['customer_custom_fields'] ?? [])
                ->map(function ($field): array {
                    return [
                        'label' => trim((string) ($field['label'] ?? '')),
                        'value' => trim((string) ($field['value'] ?? '')),
                        'show_on_invoice' => !empty($field['show_on_invoice']),
                    ];
                })
                ->filter(fn (array $field): bool => $field['label'] !== '' || $field['value'] !== '')
                ->values()
                ->all(),
        ];
    }

    private function applyItemsAndStock(Invoice $invoice, array $items, float $tax, string $discountType = 'fixed', float $discountValue = 0): array
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $product = Product::lockForUpdate()->findOrFail($item['product_id']);
            $quantity = (int) $item['quantity'];

            if ($product->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'items' => ['Stock insuffisant pour le produit: ' . $product->name],
                ]);
            }

            $unitPrice = (float) $product->unit_price;
            $lineTotal = $unitPrice * $quantity;
            $subtotal += $lineTotal;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);

            $product->decrement('quantity', $quantity);

            StockMovement::create([
                'product_id' => $product->id,
                'invoice_id' => $invoice->id,
                'type' => 'out',
                'quantity' => $quantity,
                'reference' => $invoice->invoice_number,
                'notes' => 'Sortie de stock par facture',
            ]);
        }

        $discountAmount = 0;

        if ($discountValue > 0) {
            $discountAmount = $discountType === 'percent'
                ? min($subtotal, $subtotal * ($discountValue / 100))
                : min($subtotal, $discountValue);
        }

        $netSubtotal = max(0, $subtotal - $discountAmount);
        $taxAmount = $netSubtotal * ($tax / 100);

        return [
            'subtotal' => $subtotal,
            'total' => $netSubtotal + $taxAmount,
        ];
    }

    private function restoreStockFromInvoice(Invoice $invoice, string $note): void
    {
        foreach ($invoice->items as $item) {
            $product = Product::lockForUpdate()->find($item->product_id);
            if (!$product) {
                continue;
            }

            $product->increment('quantity', $item->quantity);

            StockMovement::create([
                'product_id' => $product->id,
                'invoice_id' => $invoice->id,
                'type' => 'in',
                'quantity' => $item->quantity,
                'reference' => $invoice->invoice_number,
                'notes' => $note,
            ]);
        }
    }

    private function nextInvoiceNumber(): string
    {
        $count = Invoice::count() + 1;

        return 'FAC-' . now()->format('Ymd') . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
