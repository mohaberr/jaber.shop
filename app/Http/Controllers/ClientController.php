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
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
