@extends('layouts.app')

@section('content')
<div class="card">
    <h2 style="margin-top: 0;">Produits</h2>
    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="grid">
            <div class="field">
                <label>Nom</label>
                <input name="name" required>
            </div>
            <div class="field">
                <label>SKU</label>
                <input name="sku">
            </div>
            <div class="field">
                <label>Prix</label>
                <input name="unit_price" type="number" step="0.00001" min="0" required>
            </div>
            <div class="field">
                <label>Quantite</label>
                <input name="quantity" type="number" min="0" required>
            </div>
            <div class="field">
                <label>Seuil faible stock</label>
                <input name="low_stock_threshold" type="number" min="0" value="5" required>
            </div>
            <div class="field">
                <label>Fournisseur</label>
                <select name="supplier_id" id="supplier_id">
                    <option value="">Aucun</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Ou créer un nouveau</label>
                <input type="text" name="new_supplier_name" placeholder="Nom du fournisseur" id="new_supplier_name" style="background: #fffbf0; border-color: #f5a962;">
            </div>
            <div class="field">
                <label>Photo du produit</label>
                <input type="file" name="image" accept="image/png,image/jpeg,image/jpg,image/webp">
                <span class="muted" style="font-size: 0.85rem;">PNG, JPG, WEBP (Max: 2 Mo)</span>
            </div>
        </div>
        <div class="field">
            <label>Notes</label>
            <textarea name="notes"></textarea>
        </div>
        <button class="btn btn-main" type="submit">Ajouter produit</button>
    </form>
</div>

<script>
(() => {
    const supplierSelect = document.getElementById('supplier_id');
    const newSupplierInput = document.getElementById('new_supplier_name');

    newSupplierInput?.addEventListener('input', () => {
        if (newSupplierInput.value.trim() !== '' && supplierSelect) {
            supplierSelect.value = '';
        }
    });
})();
</script>

<div class="card desktop-table">
    <table>
        <thead>
        <tr>
            <th>Photo</th>
            <th>Nom</th>
            <th>Stock</th>
            <th>Prix</th>
            <th>Fournisseur</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($products as $product)
        <tr>
                <td>
                    @if($product->image_path)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="max-height: 40px; max-width: 60px; object-fit: cover; border-radius: 4px;">
                    @else
                        <span class="muted">-</span>
                    @endif
                </td>
                <td>{{ $product->name }}</td>
                <td>
                    {{ $product->quantity }}
                    @if($product->quantity <= $product->low_stock_threshold)
                        <span class="muted">(Stock faible)</span>
                    @endif
                </td>
                <td>{{ number_format((float) $product->unit_price, 5) }}</td>
                <td>{{ $product->supplier?->name ?? '-' }}</td>
                <td class="actions">
                    <a class="btn btn-alt" href="{{ route('products.edit', $product) }}">Modifier</a>
                    <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Supprimer ce produit ?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="card-list">
    @foreach($products as $product)
        <div class="card mobile-stack">
            @if($product->image_path)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 8px;">
            @else
                <div style="width: 100%; height: 150px; background: #f0f0f0; border-radius: 8px; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; color: #999;">Pas d'image</div>
            @endif
            <strong>{{ $product->name }}</strong>
            <div>Stock: {{ $product->quantity }}</div>
            <div>Prix: {{ number_format((float) $product->unit_price, 5) }}</div>
            <div>Fournisseur: {{ $product->supplier?->name ?? '-' }}</div>
            <div class="actions">
                <a class="btn btn-alt" href="{{ route('products.edit', $product) }}">Modifier</a>
                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Supprimer ce produit ?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">Supprimer</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
