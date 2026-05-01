<?php

namespace App\Http\Controllers;

use App\Enums\HealthRoutingLogTypeEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\TeamCategoryEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\Team;
use App\Services\HealthTeamRouting\HealthTeamRoutingLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AllocationThresholdController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:'.PermissionsEnum::TeamThresholdView], ['only' => [
            'index',
            'updateAllocation',
            'getTeams',
        ]]);
    }

    /**
     * Display a listing of the resource   .
     *
     * @return Response
     */
    public function index()
    {
        $teams = Team::whereIn('code', [quoteTypeCode::EBP, quoteTypeCode::RM_SPEED, quoteTypeCode::RM_NB, TeamNameEnum::GBP])->where('type', TeamTypeEnum::TEAM)->get();
        $customSequence = [quoteTypeCode::EBP, quoteTypeCode::RM_SPEED, quoteTypeCode::RM_NB, TeamNameEnum::GBP];
        $sortedTeams = $teams->sortBy(function ($team) use ($customSequence) {
            $index = array_search($team['code'], $customSequence);

            return $index === false ? PHP_INT_MAX : $index;
        })->values();
        $teams = $sortedTeams;

        return inertia('Admin/AllocationConfig/AllocationThreshold', [
            'teams' => $teams,
            'roles' => auth()->user()->roles->pluck('name')->toArray(),
        ]);
    }

    public function getTeams(Request $request)
    {
        $teams = Team::select('id', 'name', 'min_price', 'max_price')
            ->where('category', $request->category)
            ->where('type', TeamTypeEnum::TEAM)
            ->where('is_active', 1);

        if ($request->category == TeamCategoryEnum::NON_AUH->value) {
            $teams = $teams->whereIn('name', [quoteTypeCode::EBP, quoteTypeCode::RM_SPEED, quoteTypeCode::RM_NB, TeamNameEnum::GBP])->get();

            // Sort by custom sequence
            $customSequence = [quoteTypeCode::EBP, quoteTypeCode::RM_SPEED, quoteTypeCode::RM_NB, TeamNameEnum::GBP];
            $sortedTeams = $teams->sortBy(function ($team) use ($customSequence) {
                $index = array_search($team['name'], $customSequence);

                return $index === false ? PHP_INT_MAX : $index;
            })->values();

            $teams = $sortedTeams;
        } else {
            $teams = $teams->orderBy('id')->get();
        }

        return response()->json(['teams' => $teams]);
    }

    public function updateAllocation(Request $request)
    {
        $logData = [];
        $teams = $request->teams;

        if ($teams) {
            DB::transaction(function () use ($teams, $request, &$logData) {
                foreach ($teams as $team) {
                    Team::where('id', $team['team_id'])
                        ->update([
                            'min_price' => $team['min'],
                            'max_price' => $team['max'],
                            'allocation_threshold_enabled' => true,
                        ]);

                    $logData[] = $team;
                }

                if ($logData !== []) {
                    HealthTeamRoutingLogService::log(
                        HealthRoutingLogTypeEnum::CONFIGURATION,
                        $logData,
                        null,
                        null,
                        TeamCategoryEnum::tryFrom($request->category)
                    );
                }
            });
        }

        return response()->json(['message' => 'Allocation Threshold updated successfully']);
    }
}
