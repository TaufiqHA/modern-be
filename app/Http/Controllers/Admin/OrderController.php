<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    /**
     * Update the status and tracking number of an order.
     */
    public function updateStatus(Request $request, string $id, WhatsAppService $whatsAppService)
    {
        // Simple admin check
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized. Only admins can update order status.'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,processed,shipped,completed',
            'tracking_number' => 'required_if:status,shipped|string|nullable',
        ]);

        $order = Order::with('user')->findOrFail($id);

        $order->update([
            'status' => $request->status,
            'tracking_number' => $request->tracking_number,
        ]);

        // Trigger Notifications
        try {
            // Send Email
            Mail::to($order->user->email)->send(new OrderStatusUpdated($order));

            // Send WhatsApp (Mock)
            $whatsAppService->sendMessage(
                $order->user->phone ?? 'unknown',
                "Halo {$order->user->name}, status pesanan {$order->id} Anda kini: {$order->status}"
                .($order->tracking_number ? ". Nomor resi: {$order->tracking_number}" : '')
            );
        } catch (\Exception $e) {
            // In a real app, we might queue this or handle errors more gracefully.
            // For now, we'll just continue so the response is returned.
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => [
                'id' => $order->id,
                'status' => $order->status,
                'tracking_number' => $order->tracking_number,
            ],
        ]);
    }

    /**
     * Confirm the payment of an order manually by an admin.
     */
    public function verifyPayment(Request $request, string $id): JsonResponse
    {
        // Simple admin check
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized. Only admins can verify payments.'], 403);
        }

        $order = Order::findOrFail($id);

        // Validation: Ensure order is not already paid
        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Pembayaran pesanan ini sudah diverifikasi sebelumnya.',
            ], 400);
        }

        // Update data
        $order->update([
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
            'payment_status' => 'paid',
            'status' => 'processed', // Transition to next state
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pembayaran berhasil diverifikasi.',
            'data' => $order,
        ]);
    }
}
