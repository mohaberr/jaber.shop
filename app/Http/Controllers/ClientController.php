<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        return view('clients.index', [
            'clients' => Client::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Client::create($this->validated($request));

        return redirect()->route('clients.index')->with('success', 'Client ajoute.');
    }

    public function edit(Client $client)
    {
        return view('clients.edit', [
            'client' => $client,
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $client->update($this->validated($request));

        return redirect()->route('clients.index')->with('success', 'Client modifie.');
    }

    public function destroy(Client $client)
    {
        if ($client->invoices()->exists()) {
            return redirect()->route('clients.index')->with('error', 'Ce client a deja des factures.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client supprime.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.label' => ['nullable', 'string', 'max:100'],
            'custom_fields.*.value' => ['nullable', 'string', 'max:255'],
            'custom_fields.*.show_on_invoice' => ['nullable', 'boolean'],
        ]);

        $customFields = collect($validated['custom_fields'] ?? [])
            ->map(function ($field): array {
                return [
                    'label' => trim((string) ($field['label'] ?? '')),
                    'value' => trim((string) ($field['value'] ?? '')),
                    'show_on_invoice' => !empty($field['show_on_invoice']),
                ];
            })
            ->filter(fn (array $field): bool => $field['label'] !== '' || $field['value'] !== '')
            ->values()
            ->all();

        $validated['custom_fields'] = $customFields;

        return $validated;
    }
}
