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
        $user = $request->user();

        $request->validate([
            'address_id' => [
                'required',
                'exists:addresses,id,user_id,'.$user->id,
            ],
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request, $user) {
            $itemsData = $request->input('items');
            $totalAmount = 0;
            $orderType = 'ready_stock';

            // Pre-calculate total and order type to be more robust
            $items = [];
            foreach ($itemsData as $itemData) {
                $product = Product::lockForUpdate()->find($itemData['product_id']);
                $quantity = $itemData['quantity'];

                if ($product->stock < $quantity) {
                    $orderType = 'pre_order';
                }

                $subtotal = $product->price * $quantity;
                $totalAmount += $subtotal;

                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
            }

            // Create Order with final values
            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $request->address_id,
                'total_amount' => $totalAmount,
                'type' => $orderType,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $item['subtotal'],
                ]);

                // Decrement stock (Phase 5: Race Conditions)
                $product->decrement('stock', $quantity);
            }

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->load('orderItems'),
            ], 201);
        });

    }
}
