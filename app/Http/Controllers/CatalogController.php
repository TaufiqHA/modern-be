<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function categories(): JsonResponse
    {
        $categories = Cache::remember('categories', 3600, function () {
            return Category::select(['id', 'name', 'icon', 'slug'])->get();
        });

        return response()->json($categories);
    }

    /**
     * Display a listing of the collections.
     */
    public function collections(): JsonResponse
    {
        $collections = Cache::remember('collections', 3600, function () {
            return Collection::select(['id', 'title', 'slug', 'image_url'])->get();
        });

        return response()->json($collections);
    }
}
