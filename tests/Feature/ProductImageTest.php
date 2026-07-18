<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_with_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $image = UploadedFile::fake()->image('produit.jpg', 400, 400);

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Produit avec Photo',
            'sku' => 'SKU-001',
            'unit_price' => 99.99,
            'quantity' => 10,
            'low_stock_threshold' => 3,
            'image' => $image,
        ]);

        $response->assertRedirect(route('products.index'));

        $product = Product::first();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_product_image_can_be_updated(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $product = Product::create([
            'name' => 'Produit Initial',
            'unit_price' => 50,
            'quantity' => 5,
            'low_stock_threshold' => 2,
        ]);

        $newImage = UploadedFile::fake()->image('nouvelle-photo.png', 300, 300);

        $response = $this->actingAs($user)->put(route('products.update', $product), [
            'name' => 'Produit Modifie',
            'unit_price' => 75,
            'quantity' => 8,
            'low_stock_threshold' => 3,
            'image' => $newImage,
        ]);

        $response->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_product_image_can_be_removed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $image = UploadedFile::fake()->image('photo.jpg');
        $path = $image->store('products', 'public');

        $product = Product::create([
            'name' => 'Produit avec Photo',
            'unit_price' => 50,
            'quantity' => 5,
            'low_stock_threshold' => 2,
            'image_path' => $path,
        ]);

        $response = $this->actingAs($user)->put(route('products.update', $product), [
            'name' => 'Produit avec Photo',
            'unit_price' => 50,
            'quantity' => 5,
            'low_stock_threshold' => 2,
            'remove_image' => '1',
        ]);

        $response->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertNull($product->image_path);
    }
}
