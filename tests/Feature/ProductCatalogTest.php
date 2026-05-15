<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test products listing returns paginated products.
     */
    public function test_products_listing_returns_paginated_products(): void
    {
        Product::factory()->count(15)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price', 'image', 'category', 'stock'],
                ],
                'meta' => ['total', 'page', 'last_page'],
            ]);
    }

    /**
     * Test products listing can be filtered by category.
     */
    public function test_products_listing_can_be_filtered_by_category(): void
    {
        $category = Category::factory()->create(['slug' => 'sepatu']);
        Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products?category=sepatu');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test products listing can be filtered by collection.
     */
    public function test_products_listing_can_be_filtered_by_collection(): void
    {
        $collection = Collection::factory()->create(['slug' => 'summer-sale']);
        Product::factory()->create(['collection_id' => $collection->id]);
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products?collection=summer-sale');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test products listing can be searched.
     */
    public function test_products_listing_can_be_searched(): void
    {
        Product::factory()->create(['name' => 'Nike Air Max']);
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products?search=Nike');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test products listing can be filtered by price range.
     */
    public function test_products_listing_can_be_filtered_by_price_range(): void
    {
        Product::factory()->create(['price' => 100000]);
        Product::factory()->create(['price' => 500000]);
        Product::factory()->create(['price' => 1000000]);

        // Test with range
        $response = $this->getJson('/api/products?min_price=400000&max_price=600000');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price', 500000);

        // Test with only min_price
        $response = $this->getJson('/api/products?min_price=600000');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price', 1000000);

        // Test with only max_price
        $response = $this->getJson('/api/products?max_price=400000');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price', 100000);
    }

    /**
     * Test product detail endpoint.
     */
    public function test_product_detail_endpoint(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Detailed description',
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Test Product')
            ->assertJsonPath('data.description', 'Detailed description')
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description', 'price', 'stock', 'images', 'category'],
            ]);
    }

    /**
     * Test product detail returns 404 for invalid ID.
     */
    public function test_product_detail_returns_404_for_invalid_id(): void
    {
        $response = $this->getJson('/api/products/999');

        $response->assertStatus(404);
    }
}
