<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanySettingController extends Controller
{
    public function edit()
    {
        $setting = CompanySetting::current();

        return view('settings.edit', [
            'setting' => $setting,
            'invoiceFieldOptions' => CompanySetting::INVOICE_FIELD_OPTIONS,
            'invoiceFieldVisibility' => $setting->invoiceFieldVisibility(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'legal_mentions_title' => ['nullable', 'string', 'max:255'],
            'legal_mentions' => ['nullable', 'string', 'max:5000'],
            'logo' => ['nullable', 'image', 'max:2048'], // 2MB max
            'remove_logo' => ['nullable', 'boolean'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.name' => ['nullable', 'string', 'max:100'],
            'custom_fields.*.value' => ['nullable', 'string', 'max:255'],
            'invoice_field_visibility' => ['nullable', 'array'],
            'invoice_field_visibility.*' => ['string', 'in:' . implode(',', array_keys(CompanySetting::INVOICE_FIELD_OPTIONS))],
        ]);

        $setting = CompanySetting::current();
        $logoPath = $setting->logo_path;

        if (!empty($validated['remove_logo']) && $logoPath) {
            Storage::disk('public')->delete($logoPath);
            $logoPath = null;
        }

        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        $customFields = collect($validated['custom_fields'] ?? [])
            ->map(function (array $field): array {
                return [
                    'name' => trim((string) ($field['name'] ?? '')),
                    'value' => trim((string) ($field['value'] ?? '')),
                ];
            })
            ->filter(fn (array $field): bool => $field['name'] !== '' || $field['value'] !== '')
            ->values()
            ->all();

        $enabledVisibilityKeys = collect($validated['invoice_field_visibility'] ?? [])->map(fn ($v) => (string) $v)->all();
        $invoiceFieldVisibility = collect(CompanySetting::defaultInvoiceFieldVisibility())
            ->mapWithKeys(fn ($_default, $key) => [$key => in_array($key, $enabledVisibilityKeys, true)])
            ->all();

        $setting->update([
            'company_name' => $validated['company_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'tax_number' => $validated['tax_number'] ?? null,
            'default_tax_rate' => $validated['default_tax_rate'] ?? 0,
            'legal_mentions_title' => trim((string) ($validated['legal_mentions_title'] ?? '')) !== ''
                ? trim((string) $validated['legal_mentions_title'])
                : '',
            'legal_mentions' => $validated['legal_mentions'] ?? null,
            'logo_path' => $logoPath,
            'invoice_field_visibility' => $invoiceFieldVisibility,
            'custom_fields' => $customFields,
        ]);

        return redirect()->route('settings.edit')->with('success', 'Paramètres de l\'entreprise mis à jour.');
    }
}
