<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $orders = $request->user()->orders()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'status' => $order->status,
                'total' => (int) $order->total_amount,
                'date' => $order->created_at->toIso8601String(),
            ]);

        return response()->json($orders);
    }

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
            'payment_method' => 'required|string',
        ]);

        return DB::transaction(function () use ($request, $user) {
            $itemsData = $request->input('items');
            $totalAmount = 0;
            $orderType = 'ready_stock';

            // Phase 2.1 Logic: Stock & Price Check
            $items = [];
            foreach ($itemsData as $itemData) {
                $product = Product::lockForUpdate()->find($itemData['product_id']);
                $quantity = $itemData['quantity'];

                // Phase 5.1: Stock Exhaustion (422 Error)
                // Logic: If stock > 0 but less than requested quantity, it's an error.
                // If stock == 0, it becomes a pre_order (as per Stock & Status plan).
                if ($product->stock > 0 && $product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        "items.{$itemData['product_id']}.quantity" => "The requested quantity for '{$product->name}' exceeds available stock.",
                    ]);
                }

                if ($product->stock <= 0) {
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

            // Create Order
            // ID format: ORD-YYYYMMDD-XXXX (handled in booted method of Order model,
            // but let's ensure it matches the requested logic if needed).
            // Actually, the booted method does: ORD-now()->format('Ymd').'-'.Str::upper(Str::random(4))
            // which matches ORD-YYYYMMDD-XXXX.
            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $request->address_id,
                'total_amount' => $totalAmount,
                'type' => $orderType,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $request->payment_method,
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];

                // Item Snapshots (Phase 4.2)
                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $item['subtotal'],
                ]);

                // Stock Deduction (Phase 2.1 Logic Step 4)
                $product->decrement('stock', $quantity);
            }

            // Payment Integration (Mock) (Phase 2.1 Logic Step 6)
            $paymentUrl = 'https://checkout.midtrans.com/v2/vtweb/'.bin2hex(random_bytes(10));

            return response()->json([
                'order_id' => $order->id,
                'total_amount' => (int) $totalAmount,
                'payment_url' => $paymentUrl,
            ], 201);
        });
    }

    /**
     * Upload payment proof for a manual payment order.
     */
    public function uploadPaymentProof(Request $request, string $id)
    {
        $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048', // 2MB
            ],
        ]);

        $order = $request->user()->orders()->findOrFail($id);

        // Security check: only allow upload if payment method is 'manual' or similar
        // For now, we'll just allow it if it's currently unpaid.
        if ($order->payment_status !== 'unpaid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment proof has already been uploaded or order is already paid.',
            ], 400);
        }

        $path = $request->file('image')->store('payment_proofs', 'public');

        $order->update([
            'payment_proof' => $path,
            'payment_status' => 'pending_verification',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment proof uploaded successfully',
            'payment_proof_url' => asset('storage/'.$path),
        ]);
    }
}
