<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request): ProductCollection
    {
        $query = Product::with('category');

        if ($request->has('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->has('collection')) {
            $query->whereHas('collection', fn ($q) => $q->where('slug', $request->collection));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $limit = $request->integer('limit', 10);
        $products = $query->paginate($limit);

        return new ProductCollection($products);
    }

    /**
     * Display the specified product.
     */
    public function show(string $id): ProductResource
    {
        $product = Product::with(['category', 'collection'])->findOrFail($id);

        return new ProductResource($product);
    }
}
