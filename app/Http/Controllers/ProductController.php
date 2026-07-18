<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index', [
            'products' => Product::with('supplier')->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        
        if (!empty($validated['new_supplier_name'])) {
            $supplier = Supplier::firstOrCreate(['name' => trim($validated['new_supplier_name'])]);
            $validated['supplier_id'] = $supplier->id;
        }
        
        $validated['image_path'] = $this->handleImage($request);
        unset($validated['new_supplier_name']);
        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Produit ajoute.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', [
            'product' => $product,
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validated($request, $product->id);
        
        if (!empty($validated['new_supplier_name'])) {
            $supplier = Supplier::firstOrCreate(['name' => trim($validated['new_supplier_name'])]);
            $validated['supplier_id'] = $supplier->id;
        }
        
        $validated['image_path'] = $this->handleImage($request, $product->id);
        unset($validated['new_supplier_name']);
        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Produit modifie.');
    }

    public function destroy(Product $product)
    {
        if ($product->invoiceItems()->exists()) {
            return redirect()->route('products.index')->with('error', 'Ce produit existe deja dans des factures.');
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produit supprime.');
    }

    private function validated(Request $request, ?int $productId = null): array
    {
        return $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'new_supplier_name' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function handleImage(Request $request, ?int $productId = null): ?string
    {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $product = $productId ? Product::find($productId) : null;
        $imagePath = $product?->image_path;

        if (!empty($validated['remove_image']) && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            return null;
        }

        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            return $request->file('image')->store('products', 'public');
        }

        return $imagePath;
    }
}
