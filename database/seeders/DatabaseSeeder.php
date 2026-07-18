<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Création de l'administrateur par défaut
        User::updateOrCreate(
            ['email' => 'admin@jabir.com'],
            [
                'name' => 'Administrateur Jabir',
                'password' => Hash::make('secret123'),
            ]
        );

        // Initialisation des paramètres de base de l'entreprise
        CompanySetting::updateOrCreate(
            ['id' => 1],
            [
                'company_name' => 'Jabir Facturation & Stock',
                'email' => 'contact@fac-ja.shop',
                'phone' => '06 00 00 00 00',
                'address' => '10 Rue de l\'Entreprise, 75000 Paris',
                'tax_number' => 'SIRET : 123 456 789 00012',
            ]
        );
    }
}
