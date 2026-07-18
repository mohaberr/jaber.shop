<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return view('suppliers.index', [
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Supplier::create($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Fournisseur ajoute.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Fournisseur modifie.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->products()->exists()) {
            return redirect()->route('suppliers.index')->with('error', 'Ce fournisseur a encore des produits.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Fournisseur supprime.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
