<?php

namespace App\Http\Controllers;

use App\Enums\AssignmentTypeEnum;
use App\Models\CarQuoteAdvisorToOE;
use App\Models\User;
use App\Services\BusinessQuoteService;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\HealthQuoteService;
use App\Services\HomeQuoteService;
use App\Services\LifeQuoteService;
use App\Services\TravelQuoteService;
use App\Services\UserService;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $advisors = $insuranceTypes = [];
        foreach ($userTeams as $teamName) {
            if (str_contains($this->quotTypes, strtolower($teamName))) {
                array_push($insuranceTypes, $teamName);
            }
        }
        if (isset($request->leadType) && ! empty($request->leadType)) {
            $gridData = $this->crudService->getLeads('', '', '', strtolower($request->leadType));
            $suffix = $this->getQuerySuffix(strtolower($request->leadType));
            if ($request->ajax()) {
                if (! empty($request->assignedToId)) {
                    $gridData->orWhere($suffix.'.advisor_name', $request->assignedToId);
                }
                if (! empty($request->startDate)) {
                    $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startDate)->startOfDay()->toDateTimeString();
                    $dateTo = Carbon::createFromFormat('Y-m-d', $request->endDate)->endOfDay()->toDateTimeString();
                    $gridData->whereBetween($suffix.'.created_at', [$dateFrom, $dateTo]);
                }

                return DataTables::of($gridData->orderBy($suffix.'.created_at'))
                    ->addIndexColumn()
                    ->make(true);
            }
        }

        return view('leadassignment.view', compact('assignToUsers', 'advisors', 'insuranceTypes'));
    }

    private function getQuerySuffix($leadType)
    {
        $suffix = '';
        switch ($leadType) {
            case 'health':
            case 'home':
                $suffix = 'hqr';
                break;
            case 'life':
                $suffix = 'lqr';
                break;
            case 'travel':
                $suffix = 'tqr';
                break;
            case 'car':
                $suffix = 'cqr';
                break;
            case 'business':
                $suffix = 'bqr';
                break;
            default:
                break;
        }

        return $suffix;
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
            $entity = $this->{$type.'QuoteService'}->getEntityPlain($id);
            $userId = (int) $assignedToUserIdNew;
            if (Auth::user()->hasRole('WCU_ADVISOR')) {
                $entity->wcu_id = $userId;
            } else {
                $entity->advisor_id = $userId;
                $entity->assignment_type = AssignmentTypeEnum::MANUAL_ASSIGNED;
            }
            if ($type == 'car') {
                $advisorOE = CarQuoteAdvisorToOE::where('advisor_id', $userId)->first();
                if (! empty($advisorOE)) {
                    $entity->oe_id = $advisorOE->oe_id;
                }
            }
            $entity->save();
        }

        $assignedUserName = $this->userService->getUserNameById($assignedToUserIdNew);

        return 'Leads has been Assigned To '.$assignedUserName;
    }
}
