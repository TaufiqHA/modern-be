<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test categories endpoint returns all categories.
     */
    public function test_categories_endpoint_returns_all_categories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'icon', 'slug'],
            ]);
    }

    /**
     * Test collections endpoint returns all collections.
     */
    public function test_collections_endpoint_returns_all_collections(): void
    {
        Collection::factory()->count(2)->create();

        $response = $this->getJson('/api/collections');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => ['id', 'title', 'slug', 'image_url'],
            ]);
    }

    /**
     * Test categories are cached.
     */
    public function test_categories_are_cached(): void
    {
        Category::factory()->count(1)->create();

        // First call to cache it
        $this->getJson('/api/categories');

        $this->assertTrue(Cache::has('categories'));
    }

    /**
     * Test collections are cached.
     */
    public function test_collections_are_cached(): void
    {
        Collection::factory()->count(1)->create();

        // First call to cache it
        $this->getJson('/api/collections');

        $this->assertTrue(Cache::has('collections'));
    }
}
