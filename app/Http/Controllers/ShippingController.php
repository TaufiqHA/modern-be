<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ShippingController extends Controller
{
    /**
     * Calculate shipping cost using RajaOngkir API.
     */
    public function calculate(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'address_id' => [
                'required',
                'exists:addresses,id,user_id,'.$user->id,
            ],
            'weight' => 'required|integer|min:1',
            'courier' => 'required|string|in:jne,pos,tiki',
        ]);

        $address = Address::findOrFail($request->address_id);

        if (empty($address->city_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Destination city ID is missing for the selected address.',
            ], 422);
        }

        try {
            $response = Http::asForm()->withHeaders([
                'key' => config('services.rajaongkir.key'),
            ])->post(config('services.rajaongkir.base_url'), [
                'origin' => config('services.rajaongkir.origin'),
                'destination' => $address->city_id,
                'weight' => $request->weight,
                'courier' => $request->courier,
            ]);

            if ($response->failed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to retrieve shipping cost from RajaOngkir.',
                    'details' => $response->json(),
                ], $response->status());
            }

            $data = $response->json();
            $results = [];

            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $item) {
                    $results[] = [
                        'service' => $item['service'],
                        'description' => $item['description'],
                        'cost' => $item['cost'],
                        'etd' => $item['etd'],
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'origin' => 'Origin ID: '.config('services.rajaongkir.origin'),
                'destination' => 'Destination ID: '.$address->city_id,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred while calculating shipping cost.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
