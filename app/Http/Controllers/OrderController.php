<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $items = $request->input('items');
            $totalAmount = 0;
            $orderType = 'ready_stock';

            // Create Order first to get ID
            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $request->address_id,
                'total_amount' => 0, // Will update later
                'type' => 'ready_stock',
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            foreach ($items as $itemData) {
                $product = Product::lockForUpdate()->find($itemData['product_id']);
                $quantity = $itemData['quantity'];

                // Phase 3.1: Safe Stock Decrement & Order Type Logic
                if ($product->stock < $quantity) {
                    $orderType = 'pre_order';
                }

                $subtotal = $product->price * $quantity;
                $totalAmount += $subtotal;

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ]);

                // Decrement stock (Phase 5: Race Conditions)
                $product->decrement('stock', $quantity);
            }

            $order->update([
                'total_amount' => $totalAmount,
                'type' => $orderType,
            ]);

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->load('orderItems'),
            ], 201);
        });
    }
}
