<?php

namespace App\Http\Controllers;

use App\Models\HealthGroupNationality;
use App\Models\HealthNationalityGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function getGroupNationalities(Request $request): JsonResponse
    {
        try {
            $groupIds = $request->input('group_ids');
            $nationalities = HealthGroupNationality::whereIn('health_nationality_group_id', $groupIds)
                ->distinct()
                ->pluck('canonical_nationality_code');

            return response()->json([
                'success' => true,
                'data' => $nationalities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching group nationalities',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
