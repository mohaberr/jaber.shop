@php
    $company = \App\Models\CompanySetting::current();
    $legalMentions = trim((string) ($company->legal_mentions ?? ''));
    $customFields = collect($company->custom_fields ?? [])->filter(function ($field) {
        return !empty($field['name']) || !empty($field['value']);
    });
@endphp
<p>Bonjour {{ $invoice->customer_name }},</p>
<p>Veuillez trouver en pièce jointe votre facture <strong>{{ $invoice->invoice_number }}</strong> émise par <strong>{{ $company->company_name ?? 'notre société' }}</strong>.</p>
<p>Montant total à régler : <strong>{{ number_format($invoice->computedTotal(), 2) }} €</strong></p>
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
        <strong>Mentions légales :</strong><br>
        {!! nl2br(e($legalMentions)) !!}
    </p>
@endif
