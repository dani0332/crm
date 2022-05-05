<?php

namespace App\Http\Controllers;

use App\Models\QuoteStatus;
use App\Services\CRUDService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DataTables;
use App\Enums\quoteTypeCode;
use App\Models\Team;
use App\Models\User;
use DB;

class MyLeadsController extends Controller
{
    protected $crudService;
    public function __construct(CRUDService $crudService)
    {
        $this->crudService = $crudService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = User::where('id', Auth::user()->id)->first();
        $team = DB::table('teams')->where('id', $user->team_id)->first();
        $teamName = $team->name;
        if (strtolower($teamName) == strtolower(quoteTypeCode::RetailMedical) || strtolower($teamName) == strtolower(quoteTypeCode::EBP) || strtolower($teamName) == strtolower(quoteTypeCode::RM)) {
            $teamName = 'health';
        }
        if (strtolower($teamName) == strtolower(quoteTypeCode::CORPLINE) || strtolower($teamName) == strtolower(quoteTypeCode::GroupMedical) || strtolower($teamName) == strtolower(quoteTypeCode::GM)) {
            $teamName = 'business';
        }
        $parentTeamId = $team->id;
        $leadStatusList = QuoteStatus::select('id', 'text')->get();
        $allowedTeamTypes = [];
        array_push($allowedTeamTypes, ['id' => $team->id, 'name' => $teamName]);        
        $userAdditionalTeams = User::where('id', Auth::user()->id)->first()->additional_team_ids;
        if (!empty($userAdditionalTeams)) {
            if(str_contains($userAdditionalTeams, ',')) {
                $userAdditionalTeamsIds = explode(',', $userAdditionalTeams);
                $allowedTeamTypes = Team::whereIn('id', $userAdditionalTeamsIds)->pluck('id', 'name')->toArray();
            }else{
                $additionalTeam = Team::where('id', $userAdditionalTeams)->first();
                array_push($allowedTeamTypes, ['id' => $additionalTeam->id, 'name' => $additionalTeam->name]);
            }
        }
       
        if ($request->ajax()) {
            
            if(isset($request->teamType)){
                $teamName = strtolower($request->teamType); 
            }
            if(Auth::user()->isRenewalAdvisor()){
                $teamName = strtolower($request->leadType); 
            }
            $allowedTypes = ['car', 'home', 'business', 'health', 'life', 'travel'];
            $gridData = in_array($teamName, $allowedTypes) ? $this->crudService->getAdvisorLeads($request, $teamName) : [];
            return DataTables::of($gridData)
                ->addIndexColumn()
                ->make(true);
        }
        return view('myleads.view', compact('teamName', 'leadStatusList', 'allowedTeamTypes', 'parentTeamId'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, CRUDService $crudService)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
