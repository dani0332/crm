<?php

namespace App\Http\Controllers;

use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\BusinessInsuranceType;
use App\Models\BusinessQuote;
use App\Models\GroupMedicalType;
use App\Models\QuoteStatus;
use App\Models\User;
use App\Repositories\InsuranceProviderRepository;
use App\Services\ActivitiesService;
use App\Services\BusinessQuoteService;
use App\Services\CRUDService;
use App\Services\CustomerService;
use App\Services\DropdownSourceService;
use App\Services\LookupService;
use App\Traits\RolePermissionConditions;
use Auth;
use Carbon\Carbon;
use DataTables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AMTController extends Controller
{
    protected $businessQuoteService;
    protected $crudService;
    protected $lookupService;
    protected $customerService;
    protected $dropdownSourceService;
    protected $activityService;
    protected $genericModel;

    public const TYPE = quoteTypeCode::Business;
    use RolePermissionConditions;

    public function __construct(
        BusinessQuoteService $businessQuoteService,
        CRUDService $crudService,
        LookupService $lookupService,
        CustomerService $customerService,
        DropdownSourceService $dropdownSourceService,
        ActivitiesService $activityService
    ) {
        $this->businessQuoteService = $businessQuoteService;
        $this->crudService = $crudService;
        $this->lookupService = $lookupService;
        $this->customerService = $customerService;
        $this->dropdownSourceService = $dropdownSourceService;
        $this->activityService = $activityService;
        $this->genericModel = $this->businessQuoteService->getGenericModel(self::TYPE);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = DB::table('business_quote_request as bqr')
            ->leftJoin('business_quote_request_detail as bqrd', 'bqr.id', '=', 'bqrd.business_quote_request_id')
            ->leftJoin('business_type_of_insurance as bit', 'bqr.business_type_of_insurance_id', '=', 'bit.id')
            ->leftJoin('users as u', 'bqr.advisor_id', '=', 'u.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'bqrd.lost_reason_id')
            ->leftJoin('quote_status as qs', 'bqr.quote_status_id', '=', 'qs.id')
            ->where('bit.text', '=', quoteStatusCode::GROUP_MEDICAL)
            ->select(
                'bqr.id',
                'bqr.code',
                'bqr.uuid as uuid',
                'bqr.first_name',
                'bqr.last_name',
                'qs.text as leadStatus',
                'bqr.created_at',
                'bqr.updated_at',
                'bit.text as leadType',
                'bqr.advisor_id',
                'bqr.source',
                'ls.text as lost_reason',
                'u.name as advisor_id_text',
                'bqr.premium',
                'bqr.company_name',
                'bqrd.next_followup_date',
                'bqr.policy_number',
                'bqr.renewal_batch',
                'bqr.renewal_import_code',
                'bqr.previous_quote_policy_number',
                'bqr.previous_policy_expiry_date',
                'bqr.device',
                'bqr.previous_quote_policy_premium',
                'bqr.customer_id',
                'bqr.parent_duplicate_quote_id'
            )->orderBy('bqr.advisor_id');
        if (Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::Business) || Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::Amt) || Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::GM)) {
            // if user has advisor Role then fetch leads assigned to the user only
            $data->where('bqr.advisor_id', Auth::user()->id);    // fetch leads assigned to the user
        }
        $this->whereBasedOnRole($data, 'bqr');

        $leadStatuses = $this->dropdownSourceService->getDropdownSource('quote_status_id', QuoteTypeId::Business);

        $advisors = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn('r.name', ['GM_ADVISOR'])
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"))->orderBy('r.name')->distinct()->get();
        $isManagerORDeputy = Auth::user()->isManagerORDeputy();
        $model = 'Business';
        if ($request->ajax()) {
            if (
                empty($request->email) && empty($request->code) && empty($request->first_name) &&
                empty($request->last_name) && empty($request->quote_status_id) && empty($request->mobile_no)
            ) {
                $data->where('bqr.quote_status_id', '!=', QuoteStatusEnum::Fake);
            }
            if (isset($request->first_name) && $request->first_name != '') {
                $data->where('bqr.first_name', 'like', '%'.$request->first_name.'%');
            }
            if (isset($request->created_at_start) && $request->created_at_start != '' && isset($request->created_at_end) && $request->created_at_end != '') {
                $dateFrom = $this->parseDate($request['created_at_start'], true);
                $dateTo = $this->parseDate($request['created_at_end'], false);
                $data->whereBetween('bqr.created_at', [$dateFrom, $dateTo]);
            }
            if (isset($request->last_name) && $request->last_name != '') {
                $data->where('bqr.last_name', 'like', '%'.$request->last_name.'%');
            }
            if (isset($request->email) && $request->email != '') {
                $data->where('bqr.email', 'like', '%'.$request->email.'%');
            }
            if (isset($request->code) && $request->code != '') {
                $data->where('bqr.code', '=', $request->code);
            }
            if (isset($request->mobile_no) && $request->mobile_no != '') {
                $data->where('bqr.mobile_no', '=', $request->mobile_no);
            }
            if (isset($request->leadStatus) && $request->leadStatus != '') {
                $data->where('qs.id', '=', $request->leadStatus);
            }
            if (isset($request->advisor_id) && $request->advisor_id != '') {
                $request->advisor_id == '-1' ? $data->whereNull('bqr.advisor_id') : $data->where('bqr.advisor_id', '=', $request->advisor_id);
            }
            if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '' && isset($request->previous_policy_expiry_date_end) && $request->previous_policy_expiry_date_end != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request->previous_policy_expiry_date)->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request->previous_policy_expiry_date_end)->endOfDay()->toDateTimeString();
                $data->whereBetween('bqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
            }
            if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
                $data->where('bqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
            }
            if (isset($request->previous_quote_policy_number) && $request->previous_quote_policy_number != '') {
                $data->where('bqr.previous_quote_policy_number', $request->previous_quote_policy_number);
            }
            if (isset($request->renewal_batch) && $request->renewal_batch != '') {
                $data->where('bqr.renewal_batch', $request->renewal_batch);
            }

            $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
            $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
            if ($column != '' && $column != 0 && $direction != '') {
                if ($column == 11) {
                    $column = 'bqr.created_at';
                }
                if ($column == 12) {
                    $column = 'bqr.updated_at';
                }
                if ($column == 8) {
                    $column = 'bqrd.next_followup_date';
                }
                $data->orderBy($column, $direction);
            } else {
                $data->orderBy('bqr.created_at', 'DESC');
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);

            return view('amt.view', compact('model', 'leadStatuses', 'advisors', 'isManagerORDeputy'));
        }

        return view('amt.view', compact('model', 'leadStatuses', 'advisors', 'isManagerORDeputy'));
    }

    private function parseDate($date, $isStartOfDay)
    {
        if ($date != '') {
            $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
            if ($isStartOfDay) {
                return Carbon::createFromFormat($dateFormat, $date)->startOfDay()->toDateString();
            } else {
                return Carbon::createFromFormat($dateFormat, $date)->endOfDay()->toDateString();
            }
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $businessInsuranceType = BusinessInsuranceType::select('id', 'text')->where('text', 'Group Medical')->get();

        return view('amt.add', compact('businessInsuranceType'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required|between:1,20',
            'last_name' => 'required|between:1,50',
            'email' => 'required|email:rfc,dns|max:150',
            'mobile_no' => 'required|regex:/(0)[0-9]/|not_regex:/[a-z]/|min:7|max:20',
            'business_type_of_insurance_id' => 'required',
            'company_name' => 'required|max:150',
            'number_of_employees' => 'required',
            'brief_details' => 'required',
        ]);
        $record = $this->businessQuoteService->saveBusinessQuote($request);
        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return Redirect::back()->with('message', $record->message)->withInput();
        } else {
            if (! isset($record->quoteUID)) {
                return redirect('medical/amt')->with('success', 'Lead has been stored');
            } else {
                return redirect('medical/amt/'.$record->quoteUID)->with('success', 'Lead has been stored');
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $quoteType = 'business';
        $businessInsuranceType = BusinessInsuranceType::select('id', 'text')->where('text', 'Group Medical')->get();
        $record = BusinessQuote::where([['uuid', $id], ['business_type_of_insurance_id', 5]])->first();
        abort_if(! $record, 404);
        $leadStatuses = $leadStatuses = $this->dropdownSourceService->getDropdownSource('quote_status_id', QuoteTypeId::Business);
        $quoteTypeId = $this->activityService->getQuoteTypeId(strtolower($quoteType));
        $lostReasons = DB::table('lost_reasons')
            ->select('id', 'text')
            ->get();
        $selectedLostReasonId = $this->crudService->getSelectedLostReason('business', $record->id);

        $selectedLeadStatus = '';
        if (isset($record->quote_status_id) && $record->quote_status_id != '') {
            $selectedLeadStatus = QuoteStatus::where('id', $record->quote_status_id)->first();
        }
        $assignedUserName = '';
        $assignedGMType = '';
        if (isset($record->group_medical_type_id) && $record->group_medical_type_id != '') {
            $assignedGMType = GroupMedicalType::where('id', $record->group_medical_type_id)->first()->text;
        }
        if (isset($record->advisor_id) && $record->advisor_id != '') {
            $assignedUser = User::where('id', $record->advisor_id)->first();
            $assignedUserName = $assignedUser->name;
        }
        $model = $this->genericModel;
        $payments = $record->payments;
        $mainPayment = $record->payments()->where('code', '=', $record->code)->first();
        $paymentMethods = $this->lookupService->getPaymentMethods();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::GroupMedical);

        $allowedDuplicateLOB = $this->crudService->getAllowedDuplicateLOB('Group Medical', $record->code);
        $advisors = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn('r.name', ['RM_ADVISOR', 'GM_ADVISOR'])
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"))->orderBy('r.name')->distinct()->get();

        if ($selectedLeadStatus != '') {
            $selectedLeadStatus = $selectedLeadStatus->text;
        }

        $customerAdditionalContacts = $this->customerService->getAdditionalContacts($record->customer_id, $record->mobile_no);
        $tiers = $this->lookupService->getTierR();

        return view('amt.show', compact(
            'businessInsuranceType',
            'record',
            'model',
            'payments',
            'mainPayment',
            'paymentMethods',
            'insuranceProviders',
            'selectedLeadStatus',
            'advisors',
            'assignedUserName',
            'assignedGMType',
            'leadStatuses',
            'lostReasons',
            'selectedLostReasonId',
            'quoteType',
            'allowedDuplicateLOB',
            'customerAdditionalContacts',
            'quoteTypeId',
            'tiers'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $businessInsuranceType = BusinessInsuranceType::select('id', 'text')->where('text', 'Group Medical')->get();
        $record = BusinessQuote::where([['uuid', $id], ['business_type_of_insurance_id', 5]])->first();
        $gmTypes = GroupMedicalType::select('id', 'text', 'description')->get();
        $GMType = DB::table('business_quote_request')
            ->join('group_medical_types as gmt', 'business_quote_request.group_medical_type_id', '=', 'gmt.id')
            ->where('business_quote_request.uuid', $id)
            ->select('gmt.text as text')
            ->first();
        $selectedGmType = '';
        if (! is_null($GMType)) {
            $selectedGmType = $GMType->text;
        }

        return view('amt.edit', compact('businessInsuranceType', 'record', 'gmTypes', 'selectedGmType'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'first_name' => 'required|max:150',
            'last_name' => 'required|max:150',
            'business_type_of_insurance_id' => 'required',
            'company_name' => 'required|max:150',
            'number_of_employees' => 'required',
            'brief_details' => 'required',
            'group_medical_type_id' => 'required',
            'premium' => 'required',
        ]);
        $this->crudService->updateModelByType('business', $request, $id);

        return redirect('medical/amt/'.$id)->with('success', 'Lead has been updated');
    }
}
