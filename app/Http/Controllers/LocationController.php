<?php

namespace App\Http\Controllers;

use App\Support\MalaysianLocations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function storePostcode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'postcode' => ['required', 'string', 'max:16'],
            'city' => ['nullable', 'string', 'max:64'],
            'state' => ['nullable', 'string', 'max:64'],
        ]);

        $location = MalaysianLocations::ensureLocationExists(
            $validated['state'] ?? null,
            $validated['city'] ?? null,
            $validated['postcode']
        );

        if (! $location) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to register postcode.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'location' => [
                'state' => $location->state,
                'city' => $location->city,
                'postcode' => $location->postcode,
            ],
        ]);
    }
}
