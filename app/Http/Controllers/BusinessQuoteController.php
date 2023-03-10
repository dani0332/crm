<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use DataTables;
use Carbon\Carbon;
use App\Models\User;
use App\Enums\RolesEnum;
use App\Enums\QuoteTypeId;
use App\Models\QuoteStatus;
use App\Enums\quoteTypeCode;
use Illuminate\Http\Request;
use App\Models\BusinessQuote;
use App\Services\CRUDService;
use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Services\LookupService;
use App\Enums\PaymentStatusEnum;
use App\Models\GroupMedicalType;
use App\Enums\GenericRequestEnum;
use App\Services\CustomerService;
use App\Services\ActivitiesService;
use App\Models\BusinessInsuranceType;
use App\Services\BusinessQuoteService;
use App\Services\DropdownSourceService;
use App\Traits\RolePermissionConditions;
use Illuminate\Support\Facades\Redirect;

class BusinessQuoteController extends Controller
{
    protected $businessQuoteService;
    protected $crudService;
    protected $lookupService;
    protected $genericModel;

    public const TYPE = quoteTypeCode::Business;
    public const TYPE_ID = QuoteTypeId::Business;

    use RolePermissionConditions;

    public function __construct(
        BusinessQuoteService $businessQuoteService,
        CRUDService $crudService,
        LookupService $lookupService,
    ) {
        $this->businessQuoteService = $businessQuoteService;
        $this->genericModel = $this->businessQuoteService->getGenericModel(self::TYPE);
        $this->crudService = $crudService;
        $this->lookupService = $lookupService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index(Request $request)
    {
        $dropdownSource = $this->businessQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $gridData = $this->businessQuoteService->getGridData($this->genericModel, $request);
        $quotes = $gridData->simplePaginate(10)->withQueryString();
        return inertia('CorpLineQuote/Index', compact('quotes', 'dropdownSource'));
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
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $businessInsuranceType = BusinessInsuranceType::select('id', 'text')->where('text', 'Group Medical')->get();

        return inertia('GroupMedicalQuote/Create', [
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
        $quoteType = strtolower($this->genericModel->modelType);
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        $allowedDuplicateLOB = $this->crudService->getAllowedDuplicateLOB($quoteType, $record->code);
        $dropdownSource = $this->businessQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);
        $quoteDetails = $this->businessQuoteService->getDetailEntity($record->id);
        $isRenewalUser = auth()->user()->isRenewalUser();
        $renewalAdvisors = $this->businessQuoteService->getRenewalAdvisors();
        $this->businessQuoteService->fillData();


        $assignmentTypes = [GenericRequestEnum::ASSIGN_WITHOUT_EMAIL => 'Without Email', GenericRequestEnum::ASSIGN_WITH_EMAIL => 'With Email'];
        $isQuoteDocumentEnabled = $this->businessQuoteService->quoteDocumentEnabled($this->genericModel->modelType);
        $quoteDocuments = $this->businessQuoteService->getQuoteDocuments($this->genericModel->modelType, $record->id);
        $displaySendPolicyButton = $this->businessQuoteService->displaySendPolicyButton($record, $quoteDocuments, self::TYPE_ID);
        $documentTypes = $this->businessQuoteService->getQuoteDocumentsForUpload(self::TYPE_ID);
        $documentTypes = collect($documentTypes)->groupBy('category');

        $activities = $this->businessQuoteService->getActivityByLeadId($record->id, strtolower($this->genericModel->modelType));
        $customerAdditionalContacts = $this->businessQuoteService->getAdditionalContacts($record->customer_id, $record->mobile_no);

        $cdnPath = config('constants.AZURE_IM_STORAGE_URL') . config('constants.AZURE_IM_STORAGE_CONTAINER') . '/';

        return inertia('CorpLineQuote/Show', [
            'quote' => $record,
            'quoteDetails' => $quoteDetails,
            'modelType' => $this->genericModel->modelType,
            'dropdownSource' => $dropdownSource,
            'leadStatuses' => $dropdownSource['quote_status_id'],
            'advisors' => $advisors,
            'renewalAdvisors' => $renewalAdvisors,
            'allowedDuplicateLOB' => $allowedDuplicateLOB,
            'assignmentTypes' => $assignmentTypes,
            'genderOptions' => $this->crudService->getGenderOptions(),
            'lostReasons' => $this->lookupService->getLostReasons(),
            'quoteDocuments' => $quoteDocuments,
            'documentTypes' => $documentTypes,
            'cdnPath' => $cdnPath,
            'memberCategories' => $this->lookupService->getMemberCategories(),
            'activities' => $activities,
            'isAdmin' => auth()->user()->isAdmin(),
            'customerAdditionalContacts' => $customerAdditionalContacts,
            'ecomTravelInsuranceQuoteUrl' => config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL'),
            'permissions' => [
                'admin' => auth()->user()->hasAnyRole([RolesEnum::Admin]),
                'isManualAllocationAllowed' => auth()->user()->isAdmin() || auth()->user()->hasRole(RolesEnum::LeadPool) ? true : false,
                'notProductionApproval' => !auth()->user()->hasRole(RolesEnum::PA),
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
                'displaySendPolicyButton' => $displaySendPolicyButton,
                'approve_payments' => auth()->user()->can(PermissionsEnum::ApprovePayments),
                'edit_payments' => auth()->user()->can(PermissionsEnum::PaymentsEdit),
                'canNotEditPayments' => auth()->user()->cannot(PermissionsEnum::PaymentsEdit),
                'auditable' => auth()->user()->can(PermissionsEnum::Auditable),
                'canNotApprovePayments' => auth()->user()->cannot(PermissionsEnum::ApprovePayments),
            ],
            'enums' => [
                'quoteStatusEnum' => QuoteStatusEnum::asArray(),
                'paymentStatusEnum' => PaymentStatusEnum::asArray(),
            ],
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
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        $dropdownSource = $this->businessQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $fieldsToUpdate = $this->businessQuoteService->getFieldsToUpdate('skipProperties');
        $customTitles = [];
        foreach ($fieldsToUpdate as $property => $value) {
            if (str_contains($value, 'title')) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            } else {
                $customTitles[$property] = ucwords(str_replace('_', ' ', $property));
            }
        }

        $fields = [];
        foreach ($fieldsToUpdate as $property => $value) {
            $value = array_diff(explode('|', $value), ['title']);
            $type = $value[0] == 'select' ? 'select' : $value[1] ?? 'text';
            $fields[$property] = [
                'type' => $type,
                'required' => in_array('required', $value),
                'readonly' => in_array('readonly', $value),
                'disabled' => in_array('disabled', $value),
                'value' => $record->$property ?? '',
                'label' => $customTitles[$property],
                'options' => $dropdownSource[$property] ?? [],
            ];
        }
        $fields['email']['disabled'] = true;
        $fields['mobile_no']['disabled'] = true;
        
        return inertia('CorpLineQuote/Edit', [
            'quote' => $record,
            'fields' => $fields,
            'modelType' => $this->genericModel->modelType,
            'dropdownSource' => $dropdownSource,
            'leadStatuses' => $dropdownSource['quote_status_id'],
            'genderOptions' => $this->crudService->getGenderOptions(),
            'lostReasons' => $this->lookupService->getLostReasons(),
            'isAdmin' => auth()->user()->isAdmin(),
            'permissions' => [
                'admin' => auth()->user()->hasAnyRole([RolesEnum::Admin]),
                'notProductionApproval' => !auth()->user()->hasRole(RolesEnum::PA),
                'auditable' => auth()->user()->can(PermissionsEnum::Auditable),
            ],
            'enums' => [
                'quoteStatusEnum' => QuoteStatusEnum::asArray(),
                'paymentStatusEnum' => PaymentStatusEnum::asArray(),
            ],
        ]);

        // return view('amt.edit', compact('businessInsuranceType', 'record', 'gmTypes', 'selectedGmType'));
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
