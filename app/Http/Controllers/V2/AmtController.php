<?php

namespace App\Http\Controllers\V2;

use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\BusinessInsuranceType;
use App\Models\BusinessQuote;
use App\Models\Emirate;
use App\Models\GroupMedicalType;
use App\Models\Nationality;
use App\Repositories\BusinessQuoteRepository;
use App\Repositories\LookupRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\QuoteMemberDetailsRepository;
use App\Repositories\QuoteStatusRepository;
use App\Services\ActivitiesService;
use App\Services\BusinessQuoteService;
use App\Services\CRUDService;
use App\Services\CustomerService;
use App\Services\DropdownSourceService;
use App\Services\LookupService;
use App\Traits\RolePermissionConditions;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AmtController extends Controller
{
    protected $businessQuoteService;
    protected $crudService;
    protected $lookupService;
    protected $customerService;
    protected $dropdownSourceService;
    protected $activityService;

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
                DB::raw('DATE_FORMAT(bqr.created_at, "%d-%b-%Y %r") as created_at'),
                DB::raw('DATE_FORMAT(bqr.updated_at, "%d-%b-%Y %r") as updated_at'),
                'bit.text as leadType',
                'bqr.advisor_id',
                'bqr.source',
                'ls.text as lost_reason',
                'u.name as advisor_id_text',
                'bqr.premium',
                'bqr.company_name',
                DB::raw('DATE_FORMAT(bqrd.next_followup_date, "%d-%m-%Y") as next_followup_date'),
                'bqr.policy_number',
                'bqr.renewal_batch',
                'bqr.renewal_import_code',
                'bqr.previous_quote_policy_number',
                DB::raw('DATE_FORMAT(bqr.previous_policy_expiry_date, "%d-%m-%Y") as previous_policy_expiry_date'),
                'bqr.device',
                'bqr.previous_quote_policy_premium',
                'bqr.customer_id',
                'bqr.parent_duplicate_quote_id'
            );
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
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['created_at_start']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['created_at_end']));
            $data->whereBetween(DB::raw('DATE(bqr.created_at)'), [$dateFrom, $dateTo]);
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
            $data->orderBy('bqr.advisor_id')->orderBy($column, $direction);
        } else {
            $data->orderBy('bqr.created_at', 'DESC')->orderBy('bqr.advisor_id');
        }

        $isManualAllocationAllowed = auth()->user()->isAdmin() ? true : $isManagerORDeputy;
        $quotes = $data->simplePaginate(15)->withQueryString();

        return inertia('GroupMedicalQuote/Index', compact('model', 'leadStatuses', 'advisors', 'isManagerORDeputy', 'quotes', 'isManualAllocationAllowed'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $businessInsuranceType = BusinessInsuranceType::select('id', 'text')->where('text', 'Group Medical')->get();

        return inertia('GroupMedicalQuote/Form', [
            'businessInsuranceType' => $businessInsuranceType,
            'quote' => new BusinessQuote(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required|max:150',
            'last_name' => 'required|max:150',
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
     * @param $uuid
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($id)
    {
        $record = BusinessQuoteRepository::getBy([
            'uuid' => $id,
            'business_type_of_insurance_id' => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical),
        ]);

        $companyType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        $data = $record->toArray();
        $record->lost_reason = $data['business_quote_request_detail']['lost_reason']['text'] ?? null;
        $record->previous_advisor_id_text = $data['previous_advisor']['name'] ?? null;
        $quoteDetails = $this->businessQuoteService->getDetailEntity($record->id);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::BUSINESS->id())->get();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $allowedDuplicateLOB = $this->crudService->getAllowedDuplicateLOB('Group Medical', $record->code);
        $customerAdditionalContacts = $this->customerService->getAdditionalContacts($record->customer_id, $record->mobile_no);
        $UBODetails = QuoteMemberDetailsRepository::getBy('quote_request_id', $record->id, QuoteTypes::BUSINESS->id(), CustomerTypeEnum::Entity);
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        $UBORelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();

        return inertia('GroupMedicalQuote/Show', [
            'quote' => $record,
            'quoteDetails' => $quoteDetails,
            'modelType' => QuoteTypes::BUSINESS,
            'quoteTypeId' => QuoteTypeId::Business,
            'allowedDuplicateLOB' => $allowedDuplicateLOB,
            'genderOptions' => $this->crudService->getGenderOptions(),
            'typeCode' => quoteTypeCode::GroupMedical,
            'lostReasons' => $lostReasons,
            'quoteStatuses' => $quoteStatuses,
            'modelType' => QuoteTypes::BUSINESS,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::GMManager),
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
            'customerAdditionalContacts' => $customerAdditionalContacts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'companyTypes' => $companyType,
            'UBOsDetails' => $UBODetails,
            'UBORelations' => $UBORelations,
            'nationalities' => $nationalities,
            'emirates' => $emirates,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \\Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($id)
    {
        $businessInsuranceType = BusinessInsuranceType::select('id', 'text')->where('text', 'Group Medical')->get();
        $record = BusinessQuote::where([['uuid', $id], ['business_type_of_insurance_id', 5]])->first();
        $gmTypes = GroupMedicalType::select('id', 'text', 'description')->get();
        $GMType = DB::table('business_quote_request')
            ->join('group_medical_types as gmt', 'business_quote_request.group_medical_type_id', '=', 'gmt.id')
            ->where('business_quote_request.uuid', $id)
            ->select('gmt.text as text', 'gmt.id as id')
            ->first();
        $selectedGmType = '';
        if (! is_null($GMType)) {
            $selectedGmType = $GMType->id;
        }

        return inertia('GroupMedicalQuote/Form', [
            'businessInsuranceType' => $businessInsuranceType,
            'quote' => $record,
            'gmTypes' => $gmTypes,
            'selectedGmType' => $selectedGmType,
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

    public function cardsView(Request $request)
    {
        $quotes = [];
        $leadStatuses = $this->dropdownSourceService->getDropdownSource('quote_status_id', QuoteTypeId::Business);

        $leadStatuses = $leadStatuses->filter(function ($item) {
            return $item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUOTED || $item->text == quoteStatusCode::FOLLOWEDUP || $item->text == quoteStatusCode::NEGOTIATION || $item->text == quoteStatusCode::PAYMENTPENDING || $item->text == quoteStatusCode::APPLICATION_PENDING || $item->text == quoteStatusCode::PLOICY_DOCUMENTS_PENDING || $item->text == quoteStatusCode::TRANSACTIONAPPROVED;
        })->toArray();

        $leadStatuses = array_map(function ($item) {
            $item['data'] = getDataAgainstStatus('Business', $item['id']);

            return $item;
        }, $leadStatuses);

        return inertia('GroupMedicalQuote/Cards', [
            'quotes' => array_values($leadStatuses),
        ]);
    }
}
