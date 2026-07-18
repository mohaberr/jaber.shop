<div class="grid">
    <div class="field">
        <label>Date facture</label>
        <input type="date" name="invoice_date" value="{{ old('invoice_date', optional($invoice->invoice_date)->format('Y-m-d') ?? now()->toDateString()) }}" required>
    </div>
    <div class="field">
        <label>Echeance</label>
        <input type="date" name="due_date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}">
    </div>
    <div class="field">
        <label>Client (existant)</label>
        <select name="client_id" id="client_select">
            <option value="">-- Nouveau client --</option>
            @foreach($clients as $client)
                <option
                    value="{{ $client->id }}"
                    data-name="{{ $client->name }}"
                    data-phone="{{ $client->phone }}"
                    data-email="{{ $client->email }}"
                    data-address="{{ $client->address }}"
                    @selected(old('client_id', $invoice->client_id) == $client->id)
                >
                    {{ $client->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label>Nom client</label>
        <input name="customer_name" value="{{ old('customer_name', $invoice->customer_name) }}" id="customer_name">
    </div>
    <div class="field">
        <label>Telephone client</label>
        <input name="customer_phone" value="{{ old('customer_phone', $invoice->customer_phone) }}" id="customer_phone">
    </div>
    <div class="field">
        <label>Email client</label>
        <input type="email" name="customer_email" value="{{ old('customer_email', $invoice->customer_email) }}" id="customer_email">
    </div>
    <div class="field">
        <label>Adresse client</label>
        <input name="customer_address" value="{{ old('customer_address', $invoice->customer_address) }}" id="customer_address">
    </div>
    <div class="field">
        <label>TVA</label>
        <input type="number" min="0" max="100" step="0.01" name="tax" value="{{ old('tax', $invoice->tax ?? 0) }}">
        <span class="muted" style="font-size: 0.85rem;">Saisir un pourcentage (ex: 20 pour 20%)</span>
    </div>
    <div class="field">
        <label>Type de réduction</label>
        <select name="discount_type" id="discount_type">
            <option value="fixed" @selected(old('discount_type', $invoice->discount_type ?? 'fixed') === 'fixed')>Montant fixe</option>
            <option value="percent" @selected(old('discount_type', $invoice->discount_type ?? 'fixed') === 'percent')>Pourcentage</option>
        </select>
    </div>
    <div class="field">
        <label>Valeur réduction</label>
        <input type="number" min="0" step="0.01" name="discount_value" id="discount_value" value="{{ old('discount_value', $invoice->discount_value ?? 0) }}">
    </div>
</div>

<div class="card" style="background: #fffbf0; border: 1px solid #f5a962; padding: 12px; margin: 14px 0;">
    <h3 style="margin-top: 0; color: #8a5400;">+ Créer et utiliser un nouveau client</h3>
    <div class="grid">
        <div class="field">
            <label>Nom</label>
            <input type="text" name="new_client_name" id="new_client_name" placeholder="Nom du nouveau client">
        </div>
        <div class="field">
            <label>Téléphone</label>
            <input type="text" name="new_client_phone" id="new_client_phone" placeholder="Téléphone">
        </div>
        <div class="field">
            <label>Email</label>
            <input type="email" name="new_client_email" id="new_client_email" placeholder="Email">
        </div>
        <div class="field">
            <label>Adresse</label>
            <input type="text" name="new_client_address" id="new_client_address" placeholder="Adresse">
        </div>
    </div>
    <div class="actions" style="margin-top: 8px;">
        <button type="button" id="confirm_new_client" class="btn btn-main">Confirmer ce nouveau client</button>
        <span id="new_client_feedback" class="muted"></span>
    </div>
</div>

<div class="field">
    <label>Notes</label>
    <textarea name="notes">{{ old('notes', $invoice->notes) }}</textarea>
</div>

<div class="card" style="padding: 12px; background: #fcfffe;">
    <h3 style="margin-top: 0;">Lignes facture</h3>
    <div id="items-wrapper">
        @foreach($items as $idx => $line)
            <div class="grid item-row" style="align-items: end; margin-bottom: 8px;">
                <div class="field">
                    <label>Produit</label>
                    <select name="items[{{ $idx }}][product_id]" required>
                        <option value="">Choisir</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((int) ($line['product_id'] ?? 0) === $product->id)>
                                {{ $product->name }} (Stock: {{ $product->quantity }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Quantite</label>
                    <input type="number" min="1" name="items[{{ $idx }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" required>
                </div>
                <button type="button" class="btn btn-danger remove-item">Retirer</button>
            </div>
        @endforeach
    </div>

    <button type="button" id="add-item" class="btn btn-alt">+ Ajouter ligne</button>
</div>

<script>
(() => {
    const products = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'quantity' => $p->quantity])->values());
    const clientSelect = document.getElementById('client_select');
    const customerName = document.getElementById('customer_name');
    const customerPhone = document.getElementById('customer_phone');
    const customerEmail = document.getElementById('customer_email');
    const customerAddress = document.getElementById('customer_address');
    const newClientName = document.getElementById('new_client_name');
    const newClientPhone = document.getElementById('new_client_phone');
    const newClientEmail = document.getElementById('new_client_email');
    const newClientAddress = document.getElementById('new_client_address');
    const confirmNewClientBtn = document.getElementById('confirm_new_client');
    const newClientFeedback = document.getElementById('new_client_feedback');
    const wrapper = document.getElementById('items-wrapper');
    const addBtn = document.getElementById('add-item');

    const syncRequiredCustomerName = () => {
        const hasSelectedClient = !!clientSelect?.value;
        const hasNewClient = !!newClientName?.value?.trim();
        customerName.required = !hasSelectedClient && !hasNewClient;
    };

    const updateCustomerFromSelectedClient = () => {
        if (!clientSelect?.value) {
            return;
        }

        const selected = clientSelect.options[clientSelect.selectedIndex];
        customerName.value = selected.dataset.name || '';
        customerPhone.value = selected.dataset.phone || '';
        customerEmail.value = selected.dataset.email || '';
        customerAddress.value = selected.dataset.address || '';

        if (newClientName) {
            newClientName.value = '';
            newClientPhone.value = '';
            newClientEmail.value = '';
            newClientAddress.value = '';
        }
    };

    clientSelect?.addEventListener('change', updateCustomerFromSelectedClient);
    clientSelect?.addEventListener('change', syncRequiredCustomerName);

    newClientName?.addEventListener('input', () => {
        if (newClientName.value.trim() !== '' && clientSelect) {
            clientSelect.value = '';
        }

        syncRequiredCustomerName();
    });

    confirmNewClientBtn?.addEventListener('click', () => {
        const name = newClientName?.value?.trim() || '';
        if (name === '') {
            newClientFeedback.textContent = 'Saisissez au moins le nom du nouveau client.';
            newClientFeedback.style.color = '#b42318';
            newClientName?.focus();
            return;
        }

        customerName.value = name;
        customerPhone.value = newClientPhone?.value || '';
        customerEmail.value = newClientEmail?.value || '';
        customerAddress.value = newClientAddress?.value || '';

        if (clientSelect) {
            clientSelect.value = '';
        }

        syncRequiredCustomerName();
        newClientFeedback.textContent = 'Nouveau client confirmé. Il sera créé à l\'enregistrement de la facture.';
        newClientFeedback.style.color = '#0f5132';
    });

    if (clientSelect?.value) {
        updateCustomerFromSelectedClient();
    }

    syncRequiredCustomerName();

    const bindRemove = (scope) => {
        scope.querySelectorAll('.remove-item').forEach((btn) => {
            btn.onclick = () => {
                const rows = wrapper.querySelectorAll('.item-row');
                if (rows.length > 1) {
                    btn.closest('.item-row').remove();
                }
            };
        });
    };

    bindRemove(document);

    addBtn.onclick = () => {
        const index = wrapper.querySelectorAll('.item-row').length;
        const row = document.createElement('div');
        row.className = 'grid item-row';
        row.style.alignItems = 'end';
        row.style.marginBottom = '8px';

        const options = ['<option value="">Choisir</option>']
            .concat(products.map((p) => `<option value="${p.id}">${p.name} (Stock: ${p.quantity})</option>`))
            .join('');

        row.innerHTML = `
            <div class="field">
                <label>Produit</label>
                <select name="items[${index}][product_id]" required>${options}</select>
            </div>
            <div class="field">
                <label>Quantite</label>
                <input type="number" min="1" name="items[${index}][quantity]" value="1" required>
            </div>
            <button type="button" class="btn btn-danger remove-item">Retirer</button>
        `;

        wrapper.appendChild(row);
        bindRemove(row);
    };
})();
</script>
