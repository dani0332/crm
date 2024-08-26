<?php

namespace App\Http\Controllers;

use App\Enums\TeamTypeEnum;
use App\Models\Team;
use App\Services\TeamService;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    use TeamHierarchyTrait;

    protected $teamService;
    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Team::with('parent')->whereNotNull('type')->orderBy('created_at', 'desc');

        if (isset($request->name) && ! empty($request->name)) {
            $name = $request->name;
            $query->whereRaw('LOWER(name) LIKE ?', [strtolower("%{$name}%")]);
        }
        $teams = $query->simplePaginate();

        return inertia('Admin/Teams/Index', [
            'teams' => $teams,

        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $products = $this->teamService->getTeams();

        return inertia('Admin/Teams/Form', [
            'products' => $products,

        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validateArray = [
            'name' => 'required',
            'type' => 'required',
            'slabs_count' => 'required|numeric',
        ];
        if (isset($request->type) && $request->type != 1 && TeamTypeEnum::TEAM || $request->type == TeamTypeEnum::SUB_TEAM) {
            $validateArray['parent_team_id'] = 'required';
        }
        $this->validate($request, $validateArray);

        $team = new Team;
        if (isset($request->type) && TeamTypeEnum::TEAM || $request->type == TeamTypeEnum::SUB_TEAM) {
            $team->parent_team_id = $request->parent_team_id;
        }
        $team->name = $request->name;
        $team->type = $request->type;
        $team->is_active = 1;
        $team->slabs_count = $request->get('slabs_count');
        $team->created_at = now();
        $team->updated_at = now();
        $team->save();

        return redirect(route('team.show', $team->id))->with('success', $team->name.' has been added');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $team = Team::where('id', $id)->first();
        if (! $team) {
            return redirect()->back()->with('message', 'Team not found');
        }
        $team->parent_team_id = $this->teamService->getTeamNameById($team->parent_team_id);

        return inertia('Admin/Teams/Show', [
            'team' => $team,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Team $team)
    {
        $products = $this->teamService->getTeams();

        return inertia('Admin/Teams/Form', [
            'products' => $products,
            'team' => $team,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validateArray = [
            'name' => 'required',
            'type' => 'required',
            'slabs_count' => 'required|numeric',
        ];
        if (isset($request->type) && $request->type == TeamTypeEnum::TEAM || $request->type == TeamTypeEnum::SUB_TEAM) {
            $validateArray['parent_team_id'] = 'required';
        }
        $this->validate($request, $validateArray);

        $team = Team::where('id', $id)->first();
        if (! $team) {
            return redirect()->back()->with('message', 'Team not found');
        }
        if (isset($request->type) && $request->type == TeamTypeEnum::TEAM || $request->type == TeamTypeEnum::SUB_TEAM) {
            $team->parent_team_id = $request->parent_team_id;
        }
        $team->name = $request->name;
        $team->type = $request->type;
        $team->is_active = $request->is_active == true ? 1 : 0;
        $team->slabs_count = $request->get('slabs_count');
        $team->created_at = now();
        $team->updated_at = now();
        $team->save();

        return redirect(route('team.show', $team->id))->with('success', 'Team has been updated');
    }
}
