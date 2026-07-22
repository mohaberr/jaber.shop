@extends('layouts.app')

@section('content')
<div class="card">
    <h2 style="margin-top: 0;">Modifier produit</h2>
    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid">
            <div class="field">
                <label>Nom</label>
                <input name="name" value="{{ old('name', $product->name) }}" required>
            </div>
            <div class="field">
                <label>SKU</label>
                <input name="sku" value="{{ old('sku', $product->sku) }}">
            </div>
            <div class="field">
                <label>Prix</label>
                <input name="unit_price" type="number" step="0.00001" min="0" value="{{ old('unit_price', $product->unit_price) }}" required>
            </div>
            <div class="field">
                <label>Quantite</label>
                <input name="quantity" type="number" min="0" value="{{ old('quantity', $product->quantity) }}" required>
            </div>
            <div class="field">
                <label>Seuil faible stock</label>
                <input name="low_stock_threshold" type="number" min="0" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" required>
            </div>
            <div class="field">
                <label>Fournisseur</label>
                <select name="supplier_id">
                    <option value="">Aucun</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id', $product->supplier_id) == $supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Ou créer un nouveau</label>
                <input type="text" name="new_supplier_name" placeholder="Nom du fournisseur" id="new_supplier_name" style="background: #fffbf0; border-color: #f5a962;">
            </div>
        </div>

        <div class="card" style="background: #fcfcfc; border: 1px dashed #ccc; padding: 14px; margin: 14px 0;">
            <label style="font-weight: 700; display: block; margin-bottom: 8px;">Photo du produit</label>
            
            @if($product->image_path)
                <div style="margin-bottom: 12px; display: flex; align-items: center; gap: 12px;">
                    <img src="{{ $product->image_url }}" alt="Photo produit" style="max-height: 80px; max-width: 150px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; padding: 4px; background: #fff;">
                    <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #b42318; font-weight: 600;">
                        <input type="checkbox" name="remove_image" value="1" style="width: auto;"> Supprimer cette photo
                    </label>
                </div>
            @else
                <p class="muted" style="margin: 0 0 8px;">Aucune photo actuellement.</p>
            @endif

            <input type="file" name="image" accept="image/png,image/jpeg,image/jpg,image/webp" style="background: #fff;">
            <span class="muted" style="font-size: 0.85rem;">PNG, JPG, WEBP (Max: 2 Mo)</span>
        </div>

        <div class="field">
            <label>Notes</label>
            <textarea name="notes">{{ old('notes', $product->notes) }}</textarea>
        </div>

        <div class="actions">
            <button class="btn btn-main" type="submit">Enregistrer</button>
            <a class="btn btn-alt" href="{{ route('products.index') }}">Retour</a>
        </div>
    </form>
</div>

<script>
(() => {
    const supplierSelect = document.querySelector('select[name="supplier_id"]');
    const newSupplierInput = document.getElementById('new_supplier_name');

    newSupplierInput?.addEventListener('input', () => {
        if (newSupplierInput.value.trim() !== '' && supplierSelect) {
            supplierSelect.value = '';
        }
    });
})();
</script>
@endsection
