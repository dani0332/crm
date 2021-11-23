<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CRUDService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\BusinessQuoteService;
use App\Services\HealthQuoteService;
use App\Services\CarQuoteService;
use App\Services\HomeQuoteService;
use App\Services\LifeQuoteService;
use App\Services\TravelQuoteService;
use App\Services\UserService;
use DataTables;
use Illuminate\Support\Facades\Redirect;

class LeadAssignmentController extends Controller
{
    protected $quotTypes;
    protected $userService;
    protected $healthQuoteService;
    protected $carQuoteService;
    protected $leadStatusService;
    protected $travelQuoteService;
    protected $lifeQuoteService;
    protected $homeQuoteService;
    protected $businessQuoteService;
    protected $crudService;

    public function __construct(
        UserService $userService,
        HealthQuoteService $healthService,
        CarQuoteService $carQuoteService,
        TravelQuoteService $travelQuoteService,
        LifeQuoteService $lifeQuoteService,
        HomeQuoteService $homeQuoteService,
        BusinessQuoteService $businessQuoteService,
        CRUDService $crudService
    ) {
        $this->healthQuoteService = $healthService;
        $this->carQuoteService = $carQuoteService;
        $this->travelQuoteService = $travelQuoteService;
        $this->lifeQuoteService = $lifeQuoteService;
        $this->homeQuoteService = $homeQuoteService;
        $this->businessQuoteService = $businessQuoteService;
        $this->userService = $userService;
        $this->crudService = $crudService;
        $this->quotTypes = 'home,health,life,business,travel,car';
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $userTeams = Auth::user()->getUserTeams(Auth::user()->id);
        $userId = Auth::user()->getTeamUserIds();
        $assignToUsers = User::whereIn('id', explode(',', $userId))->get();
        $gridData = $advisors = $insuranceTypes = [];
        foreach ($userTeams as $teamName) {
            if (str_contains($this->quotTypes, strtolower($teamName))) {
                array_push($insuranceTypes, $teamName);
            }
        }
        if (isset($request->leadType) && !empty($request->leadType)) {

            $mergedGridData = $this->crudService->getLeads('', '', '', strtolower($request->leadType));
            if ($request->ajax()) {
                foreach ($mergedGridData as $rowItem) {
                    if (!empty($request->assignedToId)) {
                        $mergedGridData = collect($mergedGridData)->where('advisor_name', $request->assignedToId)->all();
                    }
                    if (!empty($request->startDate)) {
                        $mergedGridData = collect($mergedGridData)->filter(function ($item) use ($request) {
                            $createdDate = date('Y-m-d', strtotime($item->created_at));
                            return $createdDate >= date($request->startDate) && $createdDate <= date($request->endDate);
                        })->all();
                    }
                }
                return DataTables::of(collect($mergedGridData)->sortBy('advisor_name')->toArray())
                    ->addIndexColumn()
                    ->make(true);
            }
        }
        return view('leadassignment.view', compact('assignToUsers', 'advisors', 'insuranceTypes'));
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
    public function show($id)
    {
        //
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

    public function getAdvisors(Request $request)
    {
        $advisors = [];
        $adItems = $this->crudService->getAdvisorsByModelType(ucwords($request->query()['type']));
        foreach ($adItems as $item) {
            array_push($advisors, $item->toArray());
        }
        return $advisors;
    }

    public function manualLeadAssign(Request $request)
    {
        $assignedToUserIdNew = $request->assigned_to_id_new;
        $leadsIds = $request->selectTmLeadId;
        $leadsIds = explode(',', $leadsIds);
        foreach ($leadsIds as $tmLeadsId) {
            $id = explode('|', $tmLeadsId)[0];
            $type = strtolower(explode('|', $tmLeadsId)[1]);
            $updateTmLead = $this->{$type . 'QuoteService'}->getEntityPlain($id);
            $userId = (int)$assignedToUserIdNew;
            $updateTmLead->advisor_id = $userId;
            $updateTmLead->save();
        }

        $assignedUserName = $this->userService->getUserNameById($assignedToUserIdNew);
        return 'Leads has been Assigned To ' . $assignedUserName;
    }
}
