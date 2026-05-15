<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminProductCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can update a product.
     */
    public function test_admin_can_update_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create([
            'name' => 'Original Product Name',
            'price' => 100000,
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/products/{$product->id}", [
                'name' => 'Updated Product Name',
                'price' => 150000,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Product Name');

        $this->assertEquals(150000, $response->json('data.price'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'slug' => Str::slug('Updated Product Name'),
            'price' => 150000,
        ]);
    }

    /**
     * Test admin can delete a product.
     */
    public function test_admin_can_delete_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/products/{$product->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    /**
     * Test regular user cannot update a product.
     */
    public function test_regular_user_cannot_update_product(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->patchJson("/api/admin/products/{$product->id}", [
                'name' => 'Try to Hack',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test regular user cannot delete a product.
     */
    public function test_regular_user_cannot_delete_product(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/admin/products/{$product->id}");

        $response->assertStatus(403);
    }
}
