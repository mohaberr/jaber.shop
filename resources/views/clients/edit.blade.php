@extends('layouts.app')

@section('content')
<div class="card">
    <h2 style="margin-top: 0;">Modifier client</h2>
    <form method="POST" action="{{ route('clients.update', $client) }}">
        @csrf
        @method('PUT')

        <div class="grid">
            <div class="field">
                <label>Nom</label>
                <input name="name" value="{{ old('name', $client->name) }}" required>
            </div>
            <div class="field">
                <label>Telephone</label>
                <input name="phone" value="{{ old('phone', $client->phone) }}">
            </div>
            <div class="field">
                <label>Email</label>
                <input name="email" type="email" value="{{ old('email', $client->email) }}">
            </div>
            <div class="field">
                <label>Adresse</label>
                <input name="address" value="{{ old('address', $client->address) }}">
            </div>
        </div>

        <div class="card" style="background: #f6fbff; border: 1px solid #d9e9f8; padding: 12px; margin: 12px 0;">
            <label style="font-weight: 700; display: block; margin-bottom: 8px;">Champs client personnalises</label>
            <div id="client-custom-fields-wrapper" style="display: grid; gap: 10px;">
                @php
                    $customRows = old('custom_fields', $client->custom_fields ?? []);
                    if (empty($customRows)) {
                        $customRows = [['label' => '', 'value' => '', 'show_on_invoice' => 1]];
                    }
                @endphp
                @foreach($customRows as $idx => $field)
                    <div class="grid client-custom-field-row" style="grid-template-columns: 1fr 1.2fr auto auto; align-items: end; gap: 8px;">
                        <div class="field" style="margin-bottom: 0;">
                            <label>Libelle</label>
                            <input name="custom_fields[{{ $idx }}][label]" value="{{ $field['label'] ?? '' }}" placeholder="Ex: ICE">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label>Valeur</label>
                            <input name="custom_fields[{{ $idx }}][value]" value="{{ $field['value'] ?? '' }}" placeholder="Ex: 001234567890">
                        </div>
                        <label style="display: inline-flex; align-items: center; gap: 6px; height: 40px;">
                            <input type="checkbox" name="custom_fields[{{ $idx }}][show_on_invoice]" value="1" style="width: auto;" @checked(!empty($field['show_on_invoice']))>
                            <span>Afficher facture</span>
                        </label>
                        <button type="button" class="btn btn-danger remove-client-custom-field" style="height: 40px;">X</button>
                    </div>
                @endforeach
            </div>
            <div class="actions" style="margin-top: 10px;">
                <button type="button" id="add-client-custom-field" class="btn btn-alt">+ Ajouter un champ client</button>
            </div>
        </div>

        <div class="actions">
            <button class="btn btn-main" type="submit">Enregistrer</button>
            <a class="btn btn-alt" href="{{ route('clients.index') }}">Retour</a>
        </div>
    </form>
</div>

<script>
(() => {
    const wrapper = document.getElementById('client-custom-fields-wrapper');
    const addBtn = document.getElementById('add-client-custom-field');

    if (!wrapper || !addBtn) {
        return;
    }

    const bindRemove = (scope) => {
        scope.querySelectorAll('.remove-client-custom-field').forEach((btn) => {
            btn.onclick = () => {
                const rows = wrapper.querySelectorAll('.client-custom-field-row');
                if (rows.length > 1) {
                    btn.closest('.client-custom-field-row').remove();
                }
            };
        });
    };

    bindRemove(document);

    addBtn.onclick = () => {
        const index = wrapper.querySelectorAll('.client-custom-field-row').length;
        const row = document.createElement('div');
        row.className = 'grid client-custom-field-row';
        row.style.gridTemplateColumns = '1fr 1.2fr auto auto';
        row.style.alignItems = 'end';
        row.style.gap = '8px';
        row.innerHTML = `
            <div class="field" style="margin-bottom: 0;">
                <label>Libelle</label>
                <input name="custom_fields[${index}][label]" placeholder="Ex: ICE">
            </div>
            <div class="field" style="margin-bottom: 0;">
                <label>Valeur</label>
                <input name="custom_fields[${index}][value]" placeholder="Ex: 001234567890">
            </div>
            <label style="display: inline-flex; align-items: center; gap: 6px; height: 40px;">
                <input type="checkbox" name="custom_fields[${index}][show_on_invoice]" value="1" style="width: auto;" checked>
                <span>Afficher facture</span>
            </label>
            <button type="button" class="btn btn-danger remove-client-custom-field" style="height: 40px;">X</button>
        `;

        wrapper.appendChild(row);
        bindRemove(row);
    };
})();
</script>
@endsection
