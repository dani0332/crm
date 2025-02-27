<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\Team;
use Illuminate\Http\Request;

class AllocationThresholdController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:'.PermissionsEnum::TeamThresholdView], ['only' => ['index', 'updateAllocation']]);
    }

    /**
     * Display a listing of the resource   .
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $teams = Team::whereIn('name', [quoteTypeCode::EBP, quoteTypeCode::RM_SPEED, quoteTypeCode::RM_NB, TeamNameEnum::PCP])->where('type', TeamTypeEnum::TEAM)->get();
        $customSequence = [quoteTypeCode::EBP, quoteTypeCode::RM_SPEED, quoteTypeCode::RM_NB, TeamNameEnum::PCP];
        $sortedTeams = $teams->sortBy(function ($team) use ($customSequence) {
            $index = array_search($team['name'], $customSequence);

            return $index === false ? PHP_INT_MAX : $index;
        })->values();
        $teams = $sortedTeams;

        return inertia('Admin/AllocationConfig/AllocationThreshold', [
            'teams' => $teams,

        ]);
    }

    public function updateAllocation(Request $request)
    {
        $teams = $request->teams;
        if ($teams) {
            foreach ($teams as $team) {
                Team::where('id', $team['id'])->update(['min_price' => $team['min'], 'max_price' => $team['max'], 'allocation_threshold_enabled' => true]);
            }
        }

        return response()->json(['message' => 'Allocation Threshold updated successfully']);
    }
}
