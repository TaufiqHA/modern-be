<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $data = $request->validated();

        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        $product->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil diperbarui.',
            'data' => $product,
        ]);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil dihapus.',
        ], 204);
    }

    /**
     * Display the stock logs for the specified product.
     */
    public function stockLogs(Request $request, $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $product = Product::findOrFail($id);

        $logs = $product->stockLogs()
            ->with('admin:id,name')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'qty_before' => $log->qty_before,
                    'qty_after' => $log->qty_after,
                    'adjustment' => $log->qty_after - $log->qty_before,
                    'change_type' => $log->change_type,
                    'reason' => $log->reason,
                    'admin_name' => $log->admin->name,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'product_name' => $product->name,
            'current_stock' => $product->stock,
            'logs' => $logs,
        ]);
    }
}
