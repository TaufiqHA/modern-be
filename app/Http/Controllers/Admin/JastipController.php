<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConvertJastipToPreorderRequest;
use App\Models\JastipRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JastipController extends Controller
{
    /**
     * Update the quote for a jastip request.
     */
    public function updateQuote(Request $request, string $id)
    {
        // Simple admin check
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized. Only admins can provide quotes.'], 403);
        }

        $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        $jastipRequest = JastipRequest::findOrFail($id);

        $jastipRequest->update([
            'quote' => $request->price,
            'status' => 'quotation',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation submitted successfully',
            'data' => [
                'id' => $jastipRequest->id,
                'quote' => (int) $jastipRequest->quote,
                'status' => $jastipRequest->status,
            ],
        ]);
    }

    /**
     * Convert an approved Jastip request into a Pre-Order product.
     */
    public function convertToPreorder(ConvertJastipToPreorderRequest $request, string $id): JsonResponse
    {
        $jastip = JastipRequest::findOrFail($id);

        // Validasi status jastip sebelum konversi
        // Status 'approved' or 'quotation' and quote must exist
        if (! in_array($jastip->status, ['approved', 'quotation']) || is_null($jastip->quote)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jastip must be approved with a price quote before conversion.',
            ], 400);
        }

        $product = DB::transaction(function () use ($jastip, $request) {
            $newProduct = Product::create([
                'name' => $jastip->product_name,
                'slug' => Str::slug($jastip->product_name).'-'.Str::random(5),
                'price' => $jastip->quote,
                'description' => $jastip->notes ?? "Pre-Order request from {$jastip->product_name}",
                'image_url' => $jastip->image_url,
                'category_id' => $request->category_id,
                'collection_id' => $request->collection_id,
                'stock' => $request->stock,
                'is_preorder' => true,
                'status' => 'active',
            ]);

            $jastip->update(['status' => 'converted']);

            return $newProduct;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Jastip converted to product successfully.',
            'data' => $product,
        ], 201);
    }
}
