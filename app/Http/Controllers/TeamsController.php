<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\TeamService;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TeamsController extends Controller
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
        $gridData = Team::with('parent')->whereNotNull('type');

        if ($request->ajax()) {

            if (isset($request->name) && ! empty($request->name)) {
                $name = $request->name;
                $gridData = $gridData->where(function ($query) use ($name) {
                    $query->whereRaw('LOWER(name) LIKE ?', [strtolower("%{$name}%")]);
                });
            }

            return DataTables::of($gridData->get()->sortBy('parent.name'))
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('teams.view')->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('teams.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $products = $this->teamService->getTeams();

        return view('teams.add', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validateArray = [
            'name' => 'required',
            'type' => 'required',
        ];
        if (isset($request->type) && $request->type == '2' || $request->type == '3') {
            $validateArray['parent_team_id'] = 'required';
        }
        $this->validate($request, $validateArray);

        $team = new Team();
        if (isset($request->type) && $request->type == '2' || $request->type == '3') {
            $team->parent_team_id = $request->parent_team_id;
        }
        $team->name = $request->name;
        $team->type = $request->type;
        $team->is_active = 1;
        $team->created_at = now();
        $team->updated_at = now();
        $team->save();

        if (isset($request->return_to_view)) {
            return redirect('generic/teams/'.$team->id)->with('success', 'Team has been stored');
        }

        return redirect()->back()->with('success', 'Team has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Team $team)
    {
        return view('teams.show', compact('team'));
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
        return view('teams.edit', compact('team', 'products'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
