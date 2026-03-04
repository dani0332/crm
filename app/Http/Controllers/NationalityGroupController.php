<?php

namespace App\Http\Controllers;

use App\Models\HealthNationalityGroup;
use Illuminate\Http\JsonResponse;

class NationalityGroupController extends Controller
{
    public function get(): JsonResponse
    {
        try {
            $groups = HealthNationalityGroup::all();

            return response()->json([
                'success' => true,
                'data' => $groups,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching nationality groups',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
