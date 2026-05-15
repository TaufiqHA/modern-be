<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shipping\CalculateShippingRequest;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ShippingController extends Controller
{
    /**
     * Calculate shipping cost using RajaOngkir API.
     */
    public function calculate(CalculateShippingRequest $request): JsonResponse
    {
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

            // Handle standard RajaOngkir response structure
            if (isset($data['rajaongkir']['results'][0]['costs'])) {
                foreach ($data['rajaongkir']['results'][0]['costs'] as $cost) {
                    $results[] = [
                        'service' => $cost['service'],
                        'description' => $cost['description'],
                        'cost' => $cost['cost'][0]['value'],
                        'etd' => $cost['cost'][0]['etd'].' Hari',
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'origin' => $data['rajaongkir']['origin_details']['city_name'] ?? 'Unknown',
                'destination' => $data['rajaongkir']['destination_details']['city_name'] ?? 'Unknown',
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
