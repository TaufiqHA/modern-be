<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JastipController extends Controller
{
    /**
     * Display a listing of the user's jastip requests.
     */
    public function index(Request $request)
    {
        $requests = $request->user()->jastipRequests()
            ->latest()
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'product' => $item->product_name,
                'status' => $item->status,
                'quote' => $item->quote ? (int) $item->quote : null,
            ]);

        return response()->json($requests);
    }

    /**
     * Store a newly created jastip request in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'product_link' => 'required|url',
            'image' => 'required|image|max:2048', // 2MB
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $path = $request->file('image')->store('jastip', 'public');

        $jastipRequest = $request->user()->jastipRequests()->create([
            'product_name' => $request->product_name,
            'product_link' => $request->product_link,
            'image_url' => Storage::url($path),
            'quantity' => $request->quantity,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return response()->json([
            'request_id' => $jastipRequest->id,
            'status' => $jastipRequest->status,
            'message' => 'Request submitted successfully',
        ], 201);
    }

    /**
     * Convert a quoted jastip request to an order.
     */
    public function convertToOrder(Request $request, string $id)
    {
        $user = $request->user();

        $request->validate([
            'address_id' => [
                'required',
                'exists:addresses,id,user_id,'.$user->id,
            ],
            'payment_method' => 'required|string',
        ]);

        $jastipRequest = $user->jastipRequests()->findOrFail($id);

        if ($jastipRequest->status !== 'quotation' || ! $jastipRequest->quote) {
            return response()->json([
                'status' => 'error',
                'message' => 'This request has not been quoted by admin yet or is already processed.',
            ], 422);
        }

        return DB::transaction(function () use ($jastipRequest, $request, $user) {
            $totalAmount = $jastipRequest->quote * $jastipRequest->quantity;

            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $request->address_id,
                'total_amount' => $totalAmount,
                'type' => 'jastip',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $request->payment_method,
            ]);

            $order->orderItems()->create([
                'product_name' => $jastipRequest->product_name,
                'quantity' => $jastipRequest->quantity,
                'unit_price' => $jastipRequest->quote,
                'subtotal' => $totalAmount,
            ]);

            $jastipRequest->update(['status' => 'approved']);

            return response()->json([
                'order_id' => $order->id,
                'total_amount' => (int) $totalAmount,
                'message' => 'Jastip request converted to order successfully',
            ], 201);
        });
    }
}
