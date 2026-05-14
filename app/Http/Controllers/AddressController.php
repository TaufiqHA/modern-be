<?php

namespace App\Http\Controllers;

use App\Http\Requests\Address\StoreAddressRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * Display a listing of the user's addresses.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->get()->map(function ($address) {
            return [
                'id' => $address->id,
                'label' => $address->label,
                'recipient' => $address->recipient_name,
                'phone' => $address->phone_number,
                'detail' => $address->full_address,
                'is_default' => $address->is_default,
            ];
        });

        return response()->json($addresses);
    }

    /**
     * Store a newly created address in storage.
     */
    public function store(StoreAddressRequest $request): JsonResponse
    {
        $user = $request->user();

        $address = $user->addresses()->create([
            'label' => $request->label,
            'recipient_name' => $request->recipient,
            'phone_number' => $request->phone,
            'full_address' => $request->detail,
            'is_default' => $user->addresses()->count() === 0,
        ]);

        return response()->json([
            'id' => $address->id,
            'label' => $address->label,
            'recipient' => $address->recipient_name,
            'phone' => $address->phone_number,
            'detail' => $address->full_address,
            'is_default' => $address->is_default,
        ], 201);
    }
}
