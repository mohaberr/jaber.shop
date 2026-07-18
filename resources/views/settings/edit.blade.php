@extends('layouts.app')

@section('content')
<div class="card">
    <h2 style="margin-top: 0;">Parametres de la societe (Emetteur)</h2>
    <p class="muted">Ces informations (Nom, Logo, Adresse, etc.) apparaitront sur vos factures PDF et emails envoyes.</p>

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <div class="field">
                <label>Nom de la societe *</label>
                <input name="company_name" value="{{ old('company_name', $setting->company_name) }}" required>
            </div>
            <div class="field">
                <label>Numero fiscal (Siret / TVA / ICE / IF)</label>
                <input name="tax_number" value="{{ old('tax_number', $setting->tax_number) }}" placeholder="Ex: SIRET 123 456 789">
            </div>
            <div class="field">
                <label>Email de la societe</label>
                <input type="email" name="email" value="{{ old('email', $setting->email) }}" placeholder="contact@masociete.com">
            </div>
            <div class="field">
                <label>Telephone</label>
                <input name="phone" value="{{ old('phone', $setting->phone) }}" placeholder="06 00 00 00 00">
            </div>
        </div>

        <div class="field">
            <label>Adresse de la societe</label>
            <textarea name="address" placeholder="123 Rue de l'Entreprise, 75000 Paris">{{ old('address', $setting->address) }}</textarea>
        </div>

        <div class="field">
            <label>Mentions legales (pour facture PDF)</label>
            <textarea name="legal_mentions" rows="4" placeholder="Ex: Paiement a 30 jours. Penalites de retard: ...">{{ old('legal_mentions', $setting->legal_mentions) }}</textarea>
            <span class="muted" style="font-size: 0.85rem;">Ce texte sera affiche en bas des factures PDF.</span>
        </div>

        @php
            $customFields = old('custom_fields', $setting->custom_fields ?? []);
        @endphp

        <div class="card" style="background: #f7fbff; border: 1px solid #d6e6f8; padding: 14px; margin: 14px 0;">
            <label style="font-weight: 700; display: block; margin-bottom: 8px;">Champs personnalises</label>
            <p class="muted" style="margin-top: 0;">Ajoute des informations sur mesure (ex: Responsable, RC, Site web, Banque, etc.).</p>

            <div id="custom-fields-wrapper" style="display: grid; gap: 10px;">
                @forelse($customFields as $idx => $field)
                    <div class="grid custom-field-row" style="grid-template-columns: 1fr 1.2fr auto; align-items: end; gap: 8px;">
                        <div class="field" style="margin-bottom: 0;">
                            <label>Nom du champ</label>
                            <input name="custom_fields[{{ $idx }}][name]" value="{{ $field['name'] ?? '' }}" placeholder="Ex: Registre commerce">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label>Valeur</label>
                            <input name="custom_fields[{{ $idx }}][value]" value="{{ $field['value'] ?? '' }}" placeholder="Ex: RC-12345">
                        </div>
                        <button type="button" class="btn btn-danger remove-custom-field" style="height: 40px;">X</button>
                    </div>
                @empty
                    <div class="grid custom-field-row" style="grid-template-columns: 1fr 1.2fr auto; align-items: end; gap: 8px;">
                        <div class="field" style="margin-bottom: 0;">
                            <label>Nom du champ</label>
                            <input name="custom_fields[0][name]" placeholder="Ex: Registre commerce">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label>Valeur</label>
                            <input name="custom_fields[0][value]" placeholder="Ex: RC-12345">
                        </div>
                        <button type="button" class="btn btn-danger remove-custom-field" style="height: 40px;">X</button>
                    </div>
                @endforelse
            </div>

            <div class="actions" style="margin-top: 10px;">
                <button type="button" id="add-custom-field" class="btn btn-alt">+ Ajouter un champ</button>
            </div>
        </div>

        <div class="card" style="background: #fcfcfc; border: 1px dashed #ccc; padding: 14px; margin: 14px 0;">
            <label style="font-weight: 700; display: block; margin-bottom: 8px;">Logo de la societe</label>
            
            @if($setting->logo_path)
                <div style="margin-bottom: 12px; display: flex; align-items: center; gap: 12px;">
                    <img src="{{ asset('storage/' . $setting->logo_path) }}" alt="Logo" style="max-height: 60px; max-width: 150px; object-fit: contain; border-radius: 6px; border: 1px solid #ddd; padding: 4px; background: #fff;">
                    <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #b42318; font-weight: 600;">
                        <input type="checkbox" name="remove_logo" value="1" style="width: auto;"> Supprimer ce logo
                    </label>
                </div>
            @else
                <p class="muted" style="margin: 0 0 8px;">Aucun logo configure actuellement.</p>
            @endif

            <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg,image/webp" style="background: #fff;">
            <span class="muted" style="font-size: 0.85rem;">Formats acceptes : PNG, JPG, WEBP (Max : 2 Mo).</span>
        </div>

        <div class="actions">
            <button class="btn btn-main" type="submit">Enregistrer les parametres</button>
            <a class="btn btn-alt" href="{{ route('dashboard') }}">Retour</a>
        </div>
    </form>
</div>

<script>
(() => {
    const wrapper = document.getElementById('custom-fields-wrapper');
    const addBtn = document.getElementById('add-custom-field');

    if (!wrapper || !addBtn) {
        return;
    }

    const bindRemove = (scope) => {
        scope.querySelectorAll('.remove-custom-field').forEach((btn) => {
            btn.onclick = () => {
                const rows = wrapper.querySelectorAll('.custom-field-row');
                if (rows.length > 1) {
                    btn.closest('.custom-field-row').remove();
                }
            };
        });
    };

    bindRemove(document);

    addBtn.onclick = () => {
        const index = wrapper.querySelectorAll('.custom-field-row').length;
        const row = document.createElement('div');
        row.className = 'grid custom-field-row';
        row.style.gridTemplateColumns = '1fr 1.2fr auto';
        row.style.alignItems = 'end';
        row.style.gap = '8px';
        row.innerHTML = `
            <div class="field" style="margin-bottom: 0;">
                <label>Nom du champ</label>
                <input name="custom_fields[${index}][name]" placeholder="Ex: Registre commerce">
            </div>
            <div class="field" style="margin-bottom: 0;">
                <label>Valeur</label>
                <input name="custom_fields[${index}][value]" placeholder="Ex: RC-12345">
            </div>
            <button type="button" class="btn btn-danger remove-custom-field" style="height: 40px;">X</button>
        `;
        wrapper.appendChild(row);
        bindRemove(row);
    };
})();
</script>
@endsection
