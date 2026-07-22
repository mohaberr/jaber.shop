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
                    data-custom-fields='@json($client->custom_fields ?? [])'
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
    <div class="field" style="grid-column: 1 / -1;">
        <label>Champs client personnalisés (repris automatiquement)</label>
        <div id="customer_custom_fields_preview" class="muted" style="padding: 8px 10px; border: 1px dashed #d8d8d8; border-radius: 8px; min-height: 20px;">Aucun champ personnalisé.</div>
        <div id="customer_custom_fields_inputs"></div>
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
    @php
        $selectedPaymentMethod = old('payment_method');
        $customPaymentMethod = old('custom_payment_method', $invoice->payment_method && !array_key_exists($invoice->payment_method, $paymentMethodOptions) ? $invoice->payment_method : '');
        if ($selectedPaymentMethod === null) {
            $selectedPaymentMethod = $invoice->payment_method && array_key_exists($invoice->payment_method, $paymentMethodOptions)
                ? $invoice->payment_method
                : ($customPaymentMethod !== '' ? 'autre' : '');
        }
    @endphp
    <div class="field">
        <label>Mode de paiement</label>
        <select name="payment_method" id="payment_method">
            <option value="">Choisir</option>
            @foreach($paymentMethodOptions as $value => $label)
                <option value="{{ $value }}" @selected($selectedPaymentMethod === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="field" id="custom_payment_method_field" @if($selectedPaymentMethod !== 'autre') style="display: none;" @endif>
        <label>Autre mode de paiement</label>
        <input type="text" name="custom_payment_method" id="custom_payment_method" value="{{ $customPaymentMethod }}" placeholder="Ex: Mandat, mobile money...">
    </div>
    <div class="field" style="grid-column: 1 / -1;">
        <label>Champs facture personnalisés</label>
        <div id="invoice_custom_fields_list" style="display: grid; gap: 8px;"></div>
        <button type="button" id="add-invoice-custom-field" class="btn btn-alt" style="margin-top: 8px;">+ Ajouter champ personnalisé</button>
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
    const initialCustomerCustomFields = @json(old('customer_custom_fields', $invoice->customer_custom_fields ?? []));
    const initialInvoiceCustomFields = @json(old('invoice_custom_fields', $invoice->invoice_custom_fields ?? []));
    const clientSelect = document.getElementById('client_select');
    const customerName = document.getElementById('customer_name');
    const customerPhone = document.getElementById('customer_phone');
    const customerEmail = document.getElementById('customer_email');
    const customerAddress = document.getElementById('customer_address');
    const customFieldsPreview = document.getElementById('customer_custom_fields_preview');
    const customFieldsInputs = document.getElementById('customer_custom_fields_inputs');
    const newClientName = document.getElementById('new_client_name');
    const newClientPhone = document.getElementById('new_client_phone');
    const newClientEmail = document.getElementById('new_client_email');
    const newClientAddress = document.getElementById('new_client_address');
    const confirmNewClientBtn = document.getElementById('confirm_new_client');
    const newClientFeedback = document.getElementById('new_client_feedback');
    const paymentMethod = document.getElementById('payment_method');
    const customPaymentMethodField = document.getElementById('custom_payment_method_field');
    const customPaymentMethodInput = document.getElementById('custom_payment_method');
    const invoiceCustomFieldsList = document.getElementById('invoice_custom_fields_list');
    const addInvoiceCustomFieldBtn = document.getElementById('add-invoice-custom-field');
    const wrapper = document.getElementById('items-wrapper');
    const addBtn = document.getElementById('add-item');

    const setCustomFieldInputs = (values) => {
        if (!customFieldsInputs || !customFieldsPreview) {
            return;
        }

        customFieldsInputs.innerHTML = '';

        const rows = [];
        (Array.isArray(values) ? values : []).forEach((field, index) => {
            const label = (field?.label ?? '').toString().trim();
            const value = (field?.value ?? '').toString().trim();
            if (label === '' && value === '') {
                return;
            }

            rows.push(`${label || 'Champ'}: ${value || '-'}`);

            const hiddenLabel = document.createElement('input');
            hiddenLabel.type = 'hidden';
            hiddenLabel.name = `customer_custom_fields[${index}][label]`;
            hiddenLabel.value = label;
            customFieldsInputs.appendChild(hiddenLabel);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = `customer_custom_fields[${index}][value]`;
            hidden.value = value;
            customFieldsInputs.appendChild(hidden);

            const hiddenShow = document.createElement('input');
            hiddenShow.type = 'hidden';
            hiddenShow.name = `customer_custom_fields[${index}][show_on_invoice]`;
            hiddenShow.value = !field || field.show_on_invoice ? '1' : '0';
            customFieldsInputs.appendChild(hiddenShow);
        });

        customFieldsPreview.innerHTML = rows.length ? rows.map((row) => `<div>${row}</div>`).join('') : 'Aucun champ personnalisé.';
    };

    const syncRequiredCustomerName = () => {
        const hasSelectedClient = !!clientSelect?.value;
        const hasNewClient = !!newClientName?.value?.trim();
        customerName.required = !hasSelectedClient && !hasNewClient;
    };

    const syncCustomPaymentMethod = () => {
        if (!paymentMethod || !customPaymentMethodField || !customPaymentMethodInput) {
            return;
        }

        const isOther = paymentMethod.value === 'autre';
        customPaymentMethodField.style.display = isOther ? '' : 'none';
        customPaymentMethodInput.required = isOther;

        if (!isOther) {
            customPaymentMethodInput.value = '';
        }
    };

    const renderInvoiceCustomFields = (values) => {
        if (!invoiceCustomFieldsList) {
            return;
        }

        const fields = Array.isArray(values) && values.length
            ? values
            : [{ label: '', value: '', show_on_invoice: true }];

        invoiceCustomFieldsList.innerHTML = '';

        fields.forEach((field, index) => {
            const row = document.createElement('div');
            row.className = 'grid invoice-custom-field-row';
            row.style.alignItems = 'end';
            row.innerHTML = `
                <div class="field">
                    <label>Libellé</label>
                    <input type="text" name="invoice_custom_fields[${index}][label]" value="${(field?.label ?? '').toString().replace(/"/g, '&quot;')}" placeholder="Ex: Référence bon de commande">
                </div>
                <div class="field">
                    <label>Valeur</label>
                    <input type="text" name="invoice_custom_fields[${index}][value]" value="${(field?.value ?? '').toString().replace(/"/g, '&quot;')}" placeholder="Ex: BC-2026-015">
                </div>
                <div class="field">
                    <label style="display: inline-flex; gap: 8px; align-items: center;">
                        <input type="checkbox" name="invoice_custom_fields[${index}][show_on_invoice]" value="1" ${(field && field.show_on_invoice !== false) ? 'checked' : ''}>
                        Afficher sur facture
                    </label>
                </div>
                <button type="button" class="btn btn-danger remove-invoice-custom-field">Retirer</button>
            `;

            invoiceCustomFieldsList.appendChild(row);
        });

        invoiceCustomFieldsList.querySelectorAll('.remove-invoice-custom-field').forEach((button) => {
            button.onclick = () => {
                const rows = invoiceCustomFieldsList.querySelectorAll('.invoice-custom-field-row');
                if (rows.length > 1) {
                    button.closest('.invoice-custom-field-row')?.remove();
                    reindexInvoiceCustomFields();
                }
            };
        });
    };

    const reindexInvoiceCustomFields = () => {
        invoiceCustomFieldsList?.querySelectorAll('.invoice-custom-field-row').forEach((row, index) => {
            row.querySelectorAll('input').forEach((input) => {
                input.name = input.name.replace(/invoice_custom_fields\[\d+\]/, `invoice_custom_fields[${index}]`);
            });
        });
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

        let customValues = {};
        try {
            customValues = JSON.parse(selected.dataset.customFields || '[]');
        } catch (e) {
            customValues = [];
        }
        setCustomFieldInputs(customValues);

        if (newClientName) {
            newClientName.value = '';
            newClientPhone.value = '';
            newClientEmail.value = '';
            newClientAddress.value = '';
        }
    };

    clientSelect?.addEventListener('change', updateCustomerFromSelectedClient);
    clientSelect?.addEventListener('change', syncRequiredCustomerName);
    paymentMethod?.addEventListener('change', syncCustomPaymentMethod);

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
        setCustomFieldInputs([]);

        if (clientSelect) {
            clientSelect.value = '';
        }

        syncRequiredCustomerName();
        newClientFeedback.textContent = 'Nouveau client confirmé. Il sera créé à l\'enregistrement de la facture.';
        newClientFeedback.style.color = '#0f5132';
    });

    if (clientSelect?.value) {
        updateCustomerFromSelectedClient();
    } else {
        setCustomFieldInputs(initialCustomerCustomFields || []);
    }

    renderInvoiceCustomFields(initialInvoiceCustomFields || []);
    syncCustomPaymentMethod();

    addInvoiceCustomFieldBtn?.addEventListener('click', () => {
        const currentValues = Array.from(invoiceCustomFieldsList?.querySelectorAll('.invoice-custom-field-row') ?? []).map((row) => ({
            label: row.querySelector('input[name*="[label]"]')?.value || '',
            value: row.querySelector('input[name*="[value]"]')?.value || '',
            show_on_invoice: !!row.querySelector('input[type="checkbox"]')?.checked,
        }));

        currentValues.push({ label: '', value: '', show_on_invoice: true });
        renderInvoiceCustomFields(currentValues);
    });

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
