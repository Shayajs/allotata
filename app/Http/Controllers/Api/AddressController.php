<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AddressService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    protected AddressService $addressService;

    public function __construct(AddressService $addressService)
    {
        $this->addressService = $addressService;
    }

    /**
     * Recherche d'adresses (autocomplétion)
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $type = $request->get('type'); // municipality, street, housenumber
        $limit = min((int) $request->get('limit', 5), 10);

        $results = $this->addressService->search($query, $limit, $type);

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Recherche uniquement de villes
     */
    public function searchCities(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = min((int) $request->get('limit', 5), 10);

        $results = $this->addressService->searchCities($query, $limit);

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Géocoder une adresse
     */
    public function geocode(Request $request): JsonResponse
    {
        $address = $request->get('address', '');

        $result = $this->addressService->geocode($address);

        if ($result) {
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Adresse non trouvée',
        ], 404);
    }
}
