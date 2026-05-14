<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JastipRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get aggregate statistics for the admin dashboard.
     */
    public function stats(Request $request)
    {
        // Simple admin check
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized. Only admins can view dashboard stats.'], 403);
        }

        return response()->json([
            'total_sales' => (int) Order::where('payment_status', 'paid')->sum('total_amount'),
            'active_orders' => Order::whereIn('status', ['pending', 'processed', 'shipped'])->count(),
            'pending_jastip' => JastipRequest::where('status', 'pending')->count(),
            'low_stock' => Product::where('stock', '<', 5)->count(),
        ]);
    }
}
