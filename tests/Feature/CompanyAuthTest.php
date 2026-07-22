<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login(): void
    {
        $response = $this->post(route('register.post'), [
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => 'secret123456',
            'password_confirmation' => 'secret123456',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $this->post(route('logout'));
        $this->assertGuest();

        $loginResponse = $this->post(route('login.post'), [
            'email' => 'admin@test.com',
            'password' => 'secret123456',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_user_can_update_company_settings_and_logo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->actingAs($user)->put(route('settings.update'), [
            'company_name' => 'Jabir SAS',
            'email' => 'contact@jabir.com',
            'phone' => '0612345678',
            'address' => '10 Rue de Paris',
            'tax_number' => 'SIRET 99988877700011',
            'default_tax_rate' => 20,
            'legal_mentions_title' => 'Conditions legales',
            'logo' => $file,
        ]);

        $response->assertRedirect(route('settings.edit'));
        
        $setting = CompanySetting::current();
        $this->assertEquals('Jabir SAS', $setting->company_name);
        $this->assertEquals(20.0, (float) $setting->default_tax_rate);
        $this->assertEquals('Conditions legales', $setting->legal_mentions_title);
        $this->assertNotNull($setting->logo_path);

        $this->assertTrue(Storage::disk('public')->exists($setting->logo_path));
    }
}
