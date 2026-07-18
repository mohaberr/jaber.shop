<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Client;
use Illuminate\Support\Facades\Route;

Route::get('invoices/{invoice}/public-pdf', [InvoiceController::class, 'publicPdf'])
    ->name('invoices.public-pdf')
    ->middleware('signed');

// Routes d'authentification publiques
Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');
Route::get('register', [AuthController::class, 'showRegister'])->name('register');
Route::post('register', [AuthController::class, 'register'])->name('register.post');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Routes protégées par authentification (Site en ligne sécurisé)
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        $invoices = Invoice::latest()->get();

        $totalInvoiced = $invoices->sum(fn ($invoice) => $invoice->computedTotal());
        $totalPaid = $invoices->sum(fn ($invoice) => $invoice->normalizedPaidAmount());
        $totalDue = $invoices->sum(fn ($invoice) => $invoice->amountDue());
        $statusBreakdown = collect(Invoice::STATUS_LABELS)->map(function ($label, $status) use ($invoices) {
            return [
                'status' => $status,
                'label' => $label,
                'count' => $invoices->where('status', $status)->count(),
            ];
        })->values();

        return view('dashboard', [
            'productsCount' => Product::count(),
            'suppliersCount' => Supplier::count(),
            'clientsCount' => Client::count(),
            'invoicesCount' => $invoices->count(),
            'pendingInvoicesCount' => $invoices->where('status', Invoice::STATUS_PENDING)->count(),
            'paidInvoicesCount' => $invoices->where('status', Invoice::STATUS_PAID)->count(),
            'totalInvoiced' => $totalInvoiced,
            'totalPaid' => $totalPaid,
            'totalDue' => $totalDue,
            'statusBreakdown' => $statusBreakdown,
            'lowStockProducts' => Product::whereColumn('quantity', '<=', 'low_stock_threshold')->orderBy('quantity')->limit(5)->get(),
            'recentInvoices' => $invoices->take(5),
        ]);
    })->name('dashboard');

    Route::get('settings', [CompanySettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [CompanySettingController::class, 'update'])->name('settings.update');

    Route::resource('products', ProductController::class)->except(['show', 'create']);
    Route::resource('suppliers', SupplierController::class)->except(['show', 'create']);
    Route::resource('clients', ClientController::class)->except(['show', 'create']);
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status');
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
    Route::post('invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail'])->name('invoices.send-email');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/send-whatsapp', [InvoiceController::class, 'sendWhatsapp'])->name('invoices.send-whatsapp');
});
