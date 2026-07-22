@php
    $company = \App\Models\CompanySetting::current();
    $legalMentionsTitle = trim((string) ($company->legal_mentions_title ?? ''));
    $legalMentions = trim((string) ($company->legal_mentions ?? ''));
    $customFields = collect($company->custom_fields ?? [])->filter(function ($field) {
        return !empty($field['name']) || !empty($field['value']);
    });
@endphp
<p>Bonjour {{ $invoice->customer_name }},</p>
<p>Veuillez trouver en pièce jointe votre facture <strong>{{ $invoice->invoice_number }}</strong> émise par <strong>{{ $company->company_name ?? 'notre société' }}</strong>.</p>
<p>Montant total à régler : <strong>{{ number_format($invoice->computedTotal(), 2) }} €</strong></p>
@php
    $invoiceMetaFields = collect($invoice->invoice_custom_fields ?? [])->filter(function ($field) {
        return is_array($field)
            && (!empty($field['label']) || !empty($field['value']))
            && (!array_key_exists('show_on_invoice', $field) || !empty($field['show_on_invoice']));
    });
@endphp
@if($invoice->paymentMethodLabel() || $invoiceMetaFields->isNotEmpty())
    <p>
        @if($invoice->paymentMethodLabel())
            <strong>Mode de paiement :</strong> {{ $invoice->paymentMethodLabel() }}<br>
        @endif
        @foreach($invoiceMetaFields as $field)
            <strong>{{ $field['label'] ?? 'Champ' }} :</strong> {{ $field['value'] ?? '-' }}<br>
        @endforeach
    </p>
@endif
<p>Merci de votre confiance.</p>
<hr style="border: none; border-top: 1px solid #eee; margin: 16px 0;">
<p style="font-size: 0.9em; color: #666;">
    <strong>{{ $company->company_name ?? 'Facturation & Stock' }}</strong><br>
    @if($company->address){{ $company->address }}<br>@endif
    @if($company->phone)Tél : {{ $company->phone }}<br>@endif
    @if($company->email)Email : {{ $company->email }}<br>@endif
    @foreach($customFields as $field)
        @if(!empty($field['name'])){{ $field['name'] }} : @endif{{ $field['value'] ?? '' }}<br>
    @endforeach
</p>

@if($legalMentions !== '')
    <p style="font-size: 0.85em; color: #666; margin-top: 12px;">
        @if($legalMentionsTitle !== '')
            <strong>{{ $legalMentionsTitle }} :</strong><br>
        @endif
        {!! nl2br(e($legalMentions)) !!}
    </p>
@endif
