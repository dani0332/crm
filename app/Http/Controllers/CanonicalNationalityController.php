<?php

namespace App\Http\Controllers;

use App\Models\CanonicalNationality;
use Illuminate\Http\JsonResponse;

class CanonicalNationalityController extends Controller
{
    public function get(): JsonResponse
    {
        try {
            $nationalities = CanonicalNationality::where('nationality_synonym', 0)
                ->orderBy('canonical_nationality_name')
                ->select('canonical_nationality_code', 'canonical_nationality_name')->get();

            return response()->json([
                'success' => true,
                'data' => $nationalities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching gbp nationalities',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
