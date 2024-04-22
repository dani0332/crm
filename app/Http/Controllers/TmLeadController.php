<?php

namespace App\Http\Controllers;

use App\Enums\tmLeadStatusCode;
use App\Exports\TmLeadsExport;
use App\Http\Requests\TmLeadRequest;
use App\Http\Traits\TmLeadTrait;
use App\Models\TmInsuranceType;
use App\Models\TmLead;
use App\Models\TmLeadStatus;
use App\Models\User;
use App\Services\TMLeadsService;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TmLeadController extends Controller
{
    use TmLeadTrait;

    private $teleMarketingLeadsService;

    public function __construct(TMLeadsService $tmLeadsCreateUpdateService)
    {
        $this->teleMarketingLeadsService = $tmLeadsCreateUpdateService;
        $this->middleware('permission:telemarketing-list|telemarketing-create|telemarketing-edit|telemarketing-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:telemarketing-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:telemarketing-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:telemarketing-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, TmLead $tmLead)
    {
        $handlers = User::select('users.*')
            ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
            ->leftjoin('roles', 'roles.id', 'model_has_roles.role_id')
            ->whereIn('roles.name', ['TM_ADVISOR'])->orderBy('roles.name', 'asc')->get();

        $tmInsuranceTypes = $this->getInsuranceTypes();
        $tmLeadTypes = $this->getLeadTypes();
        $tmLeadStatuses = $this->getLeadStatuses();

        if (Auth::user()->hasAnyRole(['TM_ADVISOR'])) {
            $isCurrentUserIsAdvisor = '0';
        } else {
            $isCurrentUserIsAdvisor = '1';
        }

        $queryTmLeads = $this->getTMLeadData($request, $tmLead);
        $tmLead = $queryTmLeads->paginate();

        return inertia('Telemarketing/TmLeads/Index', [
            'handlers' => $handlers,
            'isCurrentUserIsAdvisor' => $isCurrentUserIsAdvisor,
            'tmInsuranceTypes' => $tmInsuranceTypes,
            'tmLeadTypes' => $tmLeadTypes,
            'tmLeadStatuses' => $tmLeadStatuses,
            'queryTmLeads' => $tmLead,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tmLeadStatuses = $this->getLeadStatuses();
        $tmInsuranceTypes = $this->getInsuranceTypes();
        $nationalities = $this->getNationalities();
        $yearsOfDrivings = $this->getYearsOfDriving();
        $carMakes = $this->getCarMakes();
        $carModels = $this->getCarModels();
        $emiratesOfRegistrations = $this->getEmiratesOfRegistrations();
        $carTypeInsurances = $this->getCarTypeInsurances();
        $tmLeadTypes = $this->getLeadTypes();
        $handlers = User::select('users.*')
            ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
            ->leftjoin('roles', 'roles.id', 'model_has_roles.role_id')
            ->whereIn('roles.name', ['TM_ADVISOR', 'TM_DEPUTY', 'TM_MANAGER'])->orderBy('roles.name', 'asc')->get();

        if (Auth::user()->hasRole('TM_ADVISOR')) {
            $isUserTmAdvisor = '1';
        } else {
            $isUserTmAdvisor = '0';
        }

        return inertia('Telemarketing/TmLeads/Form', [
            'tmLeadStatuses' => $tmLeadStatuses,
            'tmInsuranceTypes' => $tmInsuranceTypes,
            'handlers' => $handlers,
            'nationalities' => $nationalities,
            'yearsOfDrivings' => $yearsOfDrivings,
            'carMakes' => $carMakes,
            'carModels' => $carModels,
            'emiratesOfRegistrations' => $emiratesOfRegistrations,
            'carTypeInsurances' => $carTypeInsurances,
            'tmLeadTypes' => $tmLeadTypes,
            'isUserTmAdvisor' => $isUserTmAdvisor,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TmLeadRequest $request)
    {
        $tmLeadID = $this->teleMarketingLeadsService->tmLeadsCreateUpdate($request, 'create', $tmLeadID = '');

        if (isset($request->return_to_view)) {
            return redirect('telemarketing/tmleads/'.$tmLeadID)->with('success', 'TM Lead has been stored');
        }

        return redirect()->back()->with('success', 'TM Lead has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(TmLead $tmlead)
    {
        $customerCorrectPhoneNo = mapPhoneNumber($tmlead->phone_number);
        if (Auth::user()->hasRole('TM_ADVISOR') && Auth::user()->id != $tmlead->assigned_to_id) {
            return redirect()->route('tmleads.index')->with('message', "You don't have access to view this lead");
        }

        $tmLeadStatusCode = TmLeadStatus::where('id', '=', $tmlead->tm_lead_statuses_id)->value('code');

        if ((($tmLeadStatusCode == tmLeadStatusCode::NoAnswer || $tmLeadStatusCode == tmLeadStatusCode::SwitchedOff) && $tmlead->no_answer_count == 3)
            || ($tmLeadStatusCode == tmLeadStatusCode::NotContactablePE || $tmLeadStatusCode == tmLeadStatusCode::CarSold
                || $tmLeadStatusCode == tmLeadStatusCode::NotEligible || $tmLeadStatusCode == tmLeadStatusCode::NotInterested
                || $tmLeadStatusCode == tmLeadStatusCode::PurchasedBeforeFirstCall || $tmLeadStatusCode == tmLeadStatusCode::PurchasedFromCompetitor
                || $tmLeadStatusCode == tmLeadStatusCode::RevivedByNewBusiness || $tmLeadStatusCode == tmLeadStatusCode::RevivedByRenewals
                || $tmLeadStatusCode == tmLeadStatusCode::WrongNumber || $tmLeadStatusCode == tmLeadStatusCode::DONOTCALL
                || $tmLeadStatusCode == tmLeadStatusCode::Duplicate || $tmLeadStatusCode == tmLeadStatusCode::Revived
                || $tmLeadStatusCode == tmLeadStatusCode::Recycled)
        ) {
            $isLeadEditable = '0';
        } else {
            $isLeadEditable = '1';
        }

        $tmLeadStatusCode = TmLeadStatus::where('id', '=', $tmlead->tm_lead_statuses_id)->value('code');
        $tmInsuranceTypeCode = TmInsuranceType::where('id', '=', $tmlead->tm_insurance_types_id)->value('code');
        $tmLeadStatuses = $this->getLeadStatuses();

        return inertia('Telemarketing/TmLeads/Show', [
            'tmlead' => $tmlead,
            'tmLeadStatusCode' => $tmLeadStatusCode,
            'tmInsuranceTypeCode' => $tmInsuranceTypeCode,
            'tmLeadStatuses' => $tmLeadStatuses,
            'isLeadEditable' => $isLeadEditable,
            'customerCorrectPhoneNo' => $customerCorrectPhoneNo,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(TmLead $tmlead)
    {
        if (Auth::user()->hasRole('TM_ADVISOR')) {
            if (Auth::user()->id != $tmlead->assigned_to_id) {
                return redirect()->route('tmleads.index')->with('message', "You don't have access to edit this lead");
            }
            $isUserTmAdvisor = '1';
        } else {
            $isUserTmAdvisor = '0';
        }
        $tmLeadStatuses = $this->getLeadStatuses();
        $tmInsuranceTypes = $this->getInsuranceTypes();
        $nationalities = $this->getNationalities();
        $yearsOfDrivings = $this->getYearsOfDriving();
        $carMakes = $this->getCarMakes();
        $carModels = $this->getCarModels();
        $emiratesOfRegistrations = $this->getEmiratesOfRegistrations();
        $carTypeInsurances = $this->getCarTypeInsurances();
        $tmLeadTypes = $this->getLeadTypes();

        $handlers = User::select('users.*')
            ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
            ->leftjoin('roles', 'roles.id', 'model_has_roles.role_id')
            ->whereIn('roles.name', ['TM_ADVISOR', 'TM_DEPUTY', 'TM_MANAGER'])->orderBy('roles.name', 'asc')->get();

        return inertia('Telemarketing/TmLeads/Form', [
            'tmlead' => $tmlead,
            'tmLeadStatuses' => $tmLeadStatuses,
            'tmInsuranceTypes' => $tmInsuranceTypes,
            'handlers' => $handlers,
            'nationalities' => $nationalities,
            'yearsOfDrivings' => $yearsOfDrivings,
            'carMakes' => $carMakes,
            'carModels' => $carModels,
            'emiratesOfRegistrations' => $emiratesOfRegistrations,
            'carTypeInsurances' => $carTypeInsurances,
            'tmLeadTypes' => $tmLeadTypes,
            'isUserTmAdvisor' => $isUserTmAdvisor,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(TmLeadRequest $request, TmLead $tmlead)
    {
        $tmLeadID = $this->teleMarketingLeadsService->tmLeadsCreateUpdate($request, 'update', $tmlead->id);

        if (isset($request->return_to_view)) {
            return redirect('telemarketing/tmleads/'.$tmLeadID)->with('success', 'TM Lead has been updated');
        }

        return redirect()->back()->with('success', 'TM Lead has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(TmLead $tmlead)
    {
        $tmlead->is_deleted = 1;
        $tmlead->save();

        return redirect()->route('tmleads.index')->with('message', 'TM Lead has been deleted');
    }

    public function tmLeadUpdate(Request $request)
    {
        $currentDateTime = date('Y-m-d H:i:s');
        $tmLeadStatusCode = TmLeadStatus::where('id', '=', $request->tm_lead_statuses_id)->value('code');

        $this->validate($request, [
            'tm_lead_statuses_id' => 'required',
            'notes' => 'max:500',
        ]);

        if ((($tmLeadStatusCode == tmLeadStatusCode::NoAnswer || $tmLeadStatusCode == tmLeadStatusCode::SwitchedOff) && $request->no_answer_count < '3')
            || ($tmLeadStatusCode == tmLeadStatusCode::PipelineNoInfo || $tmLeadStatusCode == tmLeadStatusCode::PipelineImmediate
                || $tmLeadStatusCode == tmLeadStatusCode::PipelineFuture || $tmLeadStatusCode == tmLeadStatusCode::DealingWithAnAdvisor)
        ) {
            $this->validate($request, [
                'next_followup_date' => 'required',
                'next_followup_date' => 'date_format:Y-m-d H:i:s|after_or_equal:'.$currentDateTime,
            ]);
        }

        $tmLeadID = $this->teleMarketingLeadsService->tmLeadStatusNotesUpdate($request);

        if (Auth::user()->hasRole('TM_ADVISOR')) { // advisors redirection
            $currentUserID = Auth::user()->id;

            $prioritizeLeadId = $this->teleMarketingLeadsService->tmLeadsGetPrioritizeLead($currentUserID);

            if ($prioritizeLeadId) { // if more lead in queue
                return redirect('telemarketing/tmleads/'.$prioritizeLeadId)
                    ->with('success', 'Previous Lead#'.$tmLeadID.' updated. Please proceed with below queued lead');
            } else { // if more lead not in queue
                return redirect('telemarketing/tmleads')->with('success', 'No more leads for now!');
            }
        } else { // non-advisors redirection
            return redirect('telemarketing/tmleads/'.$tmLeadID)->with('success', 'TM Lead has been updated');
        }
    }

    public function tmLeadsAssign(Request $request)
    {
        $assignedToUserIdNew = $this->teleMarketingLeadsService->tmLeadsUpdateAssignedTo($request);
        $assignedUserName = User::where('id', '=', $assignedToUserIdNew)->value('name');

        return redirect('telemarketing/tmleads')->with('success', 'TM Leads has been Assigned To '.$assignedUserName);
    }

    public function exportTMLead(Request $request, TmLead $tmLead)
    {
        $created_at_end = Carbon::parse($request->tmLeadsEndDate)->format('Y-m-d');

        $diff = Carbon::parse($request->tmLeadsStartDate)->diffInDays(Carbon::parse($created_at_end));

        if ($diff > 30) {
            return back()->with('error', 'Maximum of 120 days (created date) are allowed to be exported.');
        }

        $queryTmLeads = $this->getTMLeadData($request, $tmLead);

        return (new TmLeadsExport($queryTmLeads))->download('tm_leads');
    }

    public function getTMLeadData($request, $tmlead)
    {
        $queryTmLeads = $tmlead::select(
            'tm_leads.id as id',
            'tm_leads.customer_name as customer_name',
            'tm_leads.notes as notes',
            'tm_leads.enquiry_date as enquiry_date',
            'tm_leads.allocation_date as allocation_date',
            'tm_leads.next_followup_date as next_followup_date',
            'tm_leads.created_at as tm_created_at',
            'tm_leads.updated_at as tm_updated_at',
            'tm_leads.cdb_id as cdb_id',
            'tm_lead_statuses.code as tm_lead_status_code',
            'handlers.name as handlers_name',
            'tm_insurance_types.text as tm_insurance_types_text',
            'tm_lead_statuses.text as tm_lead_status_text',
            'tm_lead_types.text as tm_lead_type',
        )
            ->leftjoin('tm_lead_statuses', 'tm_leads.tm_lead_statuses_id', 'tm_lead_statuses.id')
            ->leftjoin('users as handlers', 'tm_leads.assigned_to_id', 'handlers.id')
            ->leftjoin('tm_insurance_types', 'tm_leads.tm_insurance_types_id', 'tm_insurance_types.id')
            ->leftjoin('tm_lead_types', 'tm_leads.tm_lead_types_id', 'tm_lead_types.id')
            ->whereRaw('tm_leads.is_deleted=0')
            ->orderByRaw('tm_leads.next_followup_date IS NULL, tm_leads.next_followup_date, tm_leads.created_at');

        if (Auth::user()->hasRole('TM_ADVISOR')) {
            $queryTmLeads->where('tm_leads.assigned_to_id', Auth::user()->id);
        }

        if (isset($request->tm_lead_statuses_id) && ! empty($request->tm_lead_statuses_id)) {
            $queryTmLeads->where('tm_leads.tm_lead_statuses_id', $request->tm_lead_statuses_id);
        }

        if (
            isset($request->searchType) && ! empty($request->searchType)
            && isset($request->searchField) && ! empty($request->searchField)
        ) {
            if ($request->searchType == 'cdbID') {
                $queryTmLeads->where('tm_leads.cdb_id', $request->searchField);
            } elseif ($request->searchType == 'emailAddress') {
                $queryTmLeads->where('tm_leads.email_address', $request->searchField);
            } elseif ($request->searchType == 'phoneNumber') {
                $queryTmLeads->where('tm_leads.phone_number', $request->searchField);
            } else {
                $queryTmLeads->where($request->searchType, $request->searchField);
            }
        }
        if (
            isset($request->searchType) && ! empty($request->searchType)
            && isset($request->tmLeadsStartDate) && ! empty($request->tmLeadsStartDate)
            && isset($request->tmLeadsEndDate) && ! empty($request->tmLeadsEndDate)
            && ($request->searchType != 'cdbID' && $request->searchType != 'emailAddress' && $request->searchType != 'phoneNumber')
        ) {
            if ($request->tmLeadsEndDate >= $request->tmLeadsStartDate) {
                $tmLeadsDateFrom = Carbon::createFromFormat('Y-m-d', urldecode($request->tmLeadsStartDate))->startOfDay()->toDateTimeString();
                $tmLeadsDateTo = Carbon::createFromFormat('Y-m-d', urldecode($request->tmLeadsEndDate))->endOfDay()->toDateTimeString();
                $queryTmLeads->whereBetween('tm_leads.'.$request->searchType, [$tmLeadsDateFrom, $tmLeadsDateTo]);
            }
        }
        if (isset($request->assigned_to_id) && ! empty($request->assigned_to_id)) {
            if ($request->assigned_to_id == 'Unassigned') {
                $queryTmLeads->where('tm_leads.assigned_to_id', '=', '')->orWhereNull('tm_leads.assigned_to_id');
            } elseif ($request->assigned_to_id == 'MyLeads') {
                $queryTmLeads->where('tm_leads.assigned_to_id', Auth::user()->id);
            } else {
                $queryTmLeads->where('tm_leads.assigned_to_id', $request->assigned_to_id);
            }
        }
        if (isset($request->tm_insurance_types_id) && ! empty($request->tm_insurance_types_id)) {
            $queryTmLeads->where('tm_leads.tm_insurance_types_id', $request->tm_insurance_types_id);
        }
        if (isset($request->tm_lead_types_id) && ! empty($request->tm_lead_types_id)) {
            $queryTmLeads->where('tm_leads.tm_lead_types_id', $request->tm_lead_types_id);
        }

        return $queryTmLeads;
    }
}
