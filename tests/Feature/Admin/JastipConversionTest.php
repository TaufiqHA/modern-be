<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\JastipRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JastipConversionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can convert approved jastip to product.
     */
    public function test_admin_can_convert_approved_jastip_to_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $jastip = JastipRequest::factory()->create([
            'status' => 'quotation',
            'quote' => 2500000,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/admin/jastip/{$jastip->id}/convert", [
                'category_id' => $category->id,
                'stock' => 10,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Jastip converted to product successfully.',
            ])
            ->assertJsonPath('data.name', $jastip->product_name)
            ->assertJsonPath('data.is_preorder', true);

        $this->assertDatabaseHas('products', [
            'name' => $jastip->product_name,
            'price' => 2500000,
            'is_preorder' => 1,
        ]);

        $this->assertDatabaseHas('jastip_requests', [
            'id' => $jastip->id,
            'status' => 'converted',
        ]);
    }

    /**
     * Test conversion fails if jastip is not approved/has no quote.
     */
    public function test_conversion_fails_if_jastip_has_no_quote(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $jastip = JastipRequest::factory()->create([
            'status' => 'pending',
            'quote' => null,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/admin/jastip/{$jastip->id}/convert", [
                'category_id' => $category->id,
                'stock' => 10,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Jastip must be approved with a price quote before conversion.',
            ]);
    }

    /**
     * Test non-admin cannot convert jastip.
     */
    public function test_non_admin_cannot_convert_jastip(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $jastip = JastipRequest::factory()->create(['status' => 'quotation', 'quote' => 1000]);

        $response = $this->actingAs($user)
            ->postJson("/api/admin/jastip/{$jastip->id}/convert", [
                'category_id' => 1,
                'stock' => 10,
            ]);

        $response->assertStatus(403);
    }
}
