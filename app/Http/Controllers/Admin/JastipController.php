<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JastipRequest;
use Illuminate\Http\Request;

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
}
