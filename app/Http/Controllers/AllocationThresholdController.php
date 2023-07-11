<?php

namespace App\Http\Controllers;

use App\Enums\quoteTypeCode;
use App\Models\Team;
use Illuminate\Http\Request;

class AllocationThresholdController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $teams = Team::whereIn('name', [quoteTypeCode::EBP, quoteTypeCode::RM_SPEED, quoteTypeCode::RM_NB])->get();
        $customSequence = [quoteTypeCode::EBP, quoteTypeCode::RM_SPEED, quoteTypeCode::RM_NB];
        $sortedTeams = $teams->sortBy(function ($team) use ($customSequence) {
            $index = array_search($team['name'], $customSequence);
            return $index === false ? PHP_INT_MAX : $index;
        });
        $teams = $sortedTeams;
        return view('allocationthreshold.view', compact('teams'));
    }

    public function updateAllocation(Request $request)
    {
        $teams = $request->teams;
        foreach ($teams as $team) {
            Team::where('id', $team['id'])->update(['min_price' => $team['min'], 'max_price' => $team['max'], 'allocation_threshold_enabled' => true]);
        }

        return true;
    }

}
