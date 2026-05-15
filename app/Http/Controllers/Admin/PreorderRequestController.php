<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePreorderRequestStatus;
use App\Models\PreorderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreorderRequestController extends Controller
{
    /**
     * Display a listing of all preorder requests.
     */
    public function index(Request $request): JsonResponse
    {
        $requests = PreorderRequest::with(['user', 'product'])
            ->latest()
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ]);
    }

    /**
     * Update the status of the specified preorder request.
     */
    public function updateStatus(UpdatePreorderRequestStatus $request, $id): JsonResponse
    {
        $preorderRequest = PreorderRequest::findOrFail($id);

        $preorderRequest->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pre-order request status updated successfully.',
            'data' => $preorderRequest,
        ]);
    }
}
