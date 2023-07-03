<?php

namespace App\Http\Controllers;

use App\Enums\quoteTypeCode;
use App\Models\Team;
use Illuminate\Http\Request;
use DataTables;

class AllocationThresholdController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $teams = Team::whereIn('name', [quoteTypeCode::RM_NB, quoteTypeCode::RM_SPEED, quoteTypeCode::EBP])->get();
        return view('allocationthreshold.view', compact('teams'));
    }

    public function updateAllocation(Request $request)
    {
        $teams = $request->teams;
        foreach ($teams as $team) {
            Team::where('id', $team['id'])->update(['min_price' => $team['min'], 'max_price' => $team['max']]);
        }
        return true;
    }

}
