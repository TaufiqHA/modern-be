<?php

namespace App\Http\Controllers;

use App\Http\Requests\Preorder\StorePreorderRequest;
use App\Models\PreorderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreorderRequestController extends Controller
{
    /**
     * Display a listing of the preorder requests for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $requests = $request->user()->preorderRequests()
            ->with('product')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ]);
    }

    /**
     * Store a newly created preorder request in storage.
     */
    public function store(StorePreorderRequest $request): JsonResponse
    {
        $preorderRequest = PreorderRequest::create([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pre-order request submitted successfully.',
            'data' => $preorderRequest,
        ], 201);
    }
}
