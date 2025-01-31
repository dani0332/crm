<?php

namespace App\Http\Controllers;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TravelQuoteEnum;
use App\Http\Requests\StoreTravelRequest;
use App\Http\Requests\TravelPlanUpdateManualProcessRequest;
use App\Http\Requests\TravelRenewalsUploadRequest;
use App\Http\Requests\UpdateTravelRequest;
use App\Models\ApplicationStorage;
use App\Models\Emirate;
use App\Models\Nationality;
use App\Models\TravelPlan;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\NationalityRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
use App\Services\LookupService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\QuoteDocumentService;
use App\Services\RenewalsUploadService;
use App\Services\Reports\RenewalBatchReportService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Services\TravelQuoteService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Response;
use Inertia\ResponseFactory;
use RuntimeException;

class TravelController extends Controller
{
    use GenericQueriesAllLobs;

    protected $travelQuoteService;
    private $renewalQuoteService;
    protected $lookupService;
    protected $crudService;
    protected $genericModel;

    public const TYPE = quoteTypeCode::Travel;
    public const TYPE_ID = QuoteTypeId::Travel;

    /**
     * TravelController constructor.
     */
    public function __construct(TravelQuoteService $travelQuoteService, LookupService $lookupService, CRUDService $crudService,
        RenewalsUploadService $renewalQuoteService)
    {
        $this->travelQuoteService = $travelQuoteService;
        $this->renewalQuoteService = $renewalQuoteService;
        $this->genericModel = $this->travelQuoteService->getGenericModel(self::TYPE);
        $this->lookupService = $lookupService;
        $this->crudService = $crudService;
    }

    /**
     * @return ResponseFactory|Response
     *
     * @throws RuntimeException
     */
    public function index(Request $request)
    {
        $searchProperties = array_flip($this->genericModel->searchProperties);
        $dropdownSource = $this->travelQuoteService->dropdownSource($searchProperties, self::TYPE_ID);
        $insurerApiStatus = PolicyIssuanceEnum::getInsurerAPIStatuses();
        $issuanceStatuses = PolicyIssuanceEnum::getAPIIssuanceStatuses(getAll: true);
        $gridData = $this->travelQuoteService->getGridData($this->genericModel, $request);
        $quotes = $gridData->simplePaginate(10)->withQueryString();
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);
        $isManager = auth()->user()->isManagerOrDeputy();
        $isManualAllocationAllowed = auth()->user()->isAdmin() ? true : $isManager;
        $authorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();

        return inertia('TravelQuote/Index', [
            'quotes' => $quotes,
            'insurerApiStatus' => $insurerApiStatus,
            'issuanceStatuses' => $issuanceStatuses,
            'dropdownSource' => $dropdownSource,
            'renewalBatches' => $renewalBatches,
            'advisors' => $advisors,
            'session' => $request->session()->only(['success', 'error', 'message']),
            'permissions' => [
                'admin' => auth()->user()->hasAnyRole([RolesEnum::Admin]),
                'travelAdvisor' => auth()->user()->hasRole(RolesEnum::TravelAdvisor),
                'isManualAllocationAllowed' => $isManualAllocationAllowed,
                'isLeadPool' => auth()->user()->isLeadPool(),
                'isManagerORDeputy' => $isManager,
            ],
            'authorizedDays' => intval($authorizedDays->value),
            'amlStatuses' => AMLStatusCode::getStatuses(),
            'insuranceProviders' => InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::Travel),
            'travelPlans' => TravelPlan::all(),
        ]);
    }

    /**
     * @return ResponseFactory|Response
     *
     * @throws RuntimeException
     */
    public function show($id)
    {
        $quoteType = strtolower($this->genericModel->modelType);
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        abort_if(! $record, 404);

        /* Start - Temporarily adding for correcting historic data */
        (new PaymentRepository)->updatePriceVatApplicableAndVat($record, $this->genericModel->modelType);
        /* End - Temporarily adding for correcting historic data */

        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(QuoteTypes::TRAVEL->value, $record);
        $allowedDuplicateLOB = $this->crudService->getAllowedDuplicateLOB($quoteType, $record->code);
        $dropdownSource = $this->travelQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);

        $paymentEntityModel = $this->{strtolower($this->genericModel->modelType).'QuoteService'}->getEntityPlain($record->id);
        $payments = $paymentEntityModel->payments;
        $paymentMethods = $this->lookupService->getPaymentMethods();

        $isNewPaymentStructure = app(SplitPaymentService::class)->isNewPaymentStructure($payments);
        if ($isNewPaymentStructure) {
            $filteredPaymentMethods = $paymentMethods;
        } else {
            $filteredPaymentMethods = $paymentMethods->filter(function ($paymentMethod) {
                return $paymentMethod->code == PaymentMethodsEnum::CreditCard;
            })->map(function ($paymentMethod) {
                return [
                    'value' => $paymentMethod->code,
                    'label' => $paymentMethod->name,
                ];
            })->values();
        }

        $leadStatuses = collect($dropdownSource['quote_status_id'])->filter(function ($value) {
            return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        })->values();
        $leadStatuses = app(CentralService::class)->lockTransactionStatus($record, self::TYPE_ID, $leadStatuses);

        if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $leadStatuses = collect($leadStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }

        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::Travel);
        $filteredInsuranceProviders = [];
        if (! empty($insuranceProviders)) {

            $filteredInsuranceProviders = $insuranceProviders->map(function ($paymentMethod) {
                return [
                    'value' => $paymentMethod->id,
                    'label' => $paymentMethod->text,
                ];
            })->sortBy('label')->values();
        }
        $payments->load(['paymentStatus', 'paymentStatusLog', 'paymentMethod', 'insuranceProvider', 'travelPlan', 'travelPlan.insuranceProvider']);

        $payments->each(function ($payment) {
            $allow = $payment->payment_status_id != PaymentStatusEnum::CAPTURED && $payment->payment_status_id != PaymentStatusEnum::AUTHORISED && ! auth()->user()->hasRole(RolesEnum::PA);
            $payment->copy_link_button = $allow && optional($payment->paymentMethod)->code == PaymentMethodsEnum::CreditCard && $payment->payment_status_id != PaymentStatusEnum::PAID;
            $payment->edit_button = $allow && $payment->payment_status_id != PaymentStatusEnum::PAID;
            $payment->approve_button = optional($payment->paymentMethod)->code != PaymentMethodsEnum::CreditCard && $payment->payment_status_id != PaymentStatusEnum::PAID && $payment->payment_status_id != PaymentStatusEnum::CAPTURED
                && ! auth()->user()->hasRole(RolesEnum::PA);

            $payment->approved_button = $payment->payment_status_id == PaymentStatusEnum::PAID;
        });

        $renewalAdvisors = $this->travelQuoteService->getRenewalAdvisors();
        $this->travelQuoteService->fillData();
        $nationalities = NationalityRepository::withActive()->get();
        $record->payment_status_id_text = app(SplitPaymentService::class)->mapQuotePaymentStatus($record->payment_status_id, $record->payment_status_id_text);
        $record->departure_country_text = $record->departure_country_id ? Nationality::find($record->departure_country_id)->country_name : null;

        $ecomDetails = [
            'premium' => $record->premium,
            'paidAt' => ($record->paid_at) ? Carbon::parse($record->paid_at)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : 'N/A',
            'paymentStatus' => $record->payment_status_id_text,
            'planName' => $record->plan_id_text,
            'providerName' => $record->travel_plan_provider_text,
            'paidAtPayment' => ($record->payment_paid_at) ? Carbon::parse($record->payment_paid_at)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : 'N/A',
        ];

        $assignmentTypes = [GenericRequestEnum::ASSIGN_WITHOUT_EMAIL => 'Without Email', GenericRequestEnum::ASSIGN_WITH_EMAIL => 'With Email'];
        $isQuoteDocumentEnabled = $this->travelQuoteService->quoteDocumentEnabled($this->genericModel->modelType);
        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments($this->genericModel->modelType, $record->id);
        $displaySendPolicyButton = $this->travelQuoteService->displaySendPolicyButton($record, $quoteDocuments, self::TYPE_ID);
        $documentTypes = $documentType = $this->travelQuoteService->getQuoteDocumentsForUpload(self::TYPE_ID);
        $documentTypes = collect($documentTypes)->groupBy('category');

        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypeId::Travel);

        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $activities = $this->travelQuoteService->getActivityByLeadId($record->id, strtolower($this->genericModel->modelType));
        $customerAdditionalContacts = $this->travelQuoteService->getAdditionalContacts($record->customer_id, $record->mobile_no);
        $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
        $fields = $this->travelQuoteService->fieldsToDisplay($this->travelQuoteService->getFieldsToShow(), $record);
        if (isset($fields['advisor_id']) && ! empty($fields['advisor_id']) && isset($fields['advisor_id']['value']) && $fields['advisor_id']['value'] === 'Customer Happiness Centre') {
            $fields['advisor_id']['value'] = 'Auto Issued';
        }
        $travelDestinations = $this->travelQuoteService->getTravelDestinations($record->id);
        if (! auth()->user()->hasRole(RolesEnum::Engineering)) {
            unset($fields['id']);
        }

        // Remove Duplicate fields which are already visible in Customer Profile Section
        $removeFields = ['first_name', 'last_name', 'email', 'mobile_no', 'dob'];
        $fields = array_diff_key($fields, array_flip($removeFields));

        $embeddedProducts = EmbeddedProductRepository::byQuoteType(self::TYPE_ID, $record->id);
        $uboDetails = CustomerMembersRepository::getBy($record->id, QuoteTypes::TRAVEL->name, CustomerTypeEnum::Entity);
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();
        $bookPolicyDetails = $this->bookPolicyPayload($record, $quoteType, $payments, $quoteDocuments);

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = $this->crudService->hasAtleastOneStatusPolicyIssued($record);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = $this->lookupService->getSendUpdateOptions(QuoteTypeId::Travel);
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($record->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($record);
        $isAmlClearedForQuote = $record->aml_status === AMLStatusCode::AMLScreeningCleared;
        $amlStatusName = AMLStatusCode::getName($record->aml_status);
        $access = $this->travelQuoteService->updatedAccessAgainstPaymentStatus($paymentEntityModel, $record);

        $lockStatusOfPolicyIssuanceSteps = (new PolicyIssuanceService)->getPolicyIssuanceStepsStatus($record, self::TYPE);

        return inertia('TravelQuote/Show', [
            'quote' => $record,
            'isAmlClearedForQuote' => $isAmlClearedForQuote,
            'amlStatusName' => $amlStatusName,
            'fieldsToDisplay' => $fields,
            'modelType' => $this->genericModel->modelType,
            'quoteTypeId' => QuoteTypeId::Travel,
            'dropdownSource' => $dropdownSource,
            'leadStatuses' => $leadStatuses,
            'advisors' => $advisors,
            'renewalAdvisors' => $renewalAdvisors,
            'allowedDuplicateLOB' => $allowedDuplicateLOB,
            'assignmentTypes' => $assignmentTypes,
            'travelDestinations' => $travelDestinations,
            'genderOptions' => $this->crudService->getGenderOptions(),
            'lostReasons' => $this->lookupService->getLostReasons(),
            'travelers' => CustomerMembersRepository::getBy($record->id, QuoteTypes::TRAVEL->name),
            'aboveAgeMembers' => $this->travelQuoteService->getAboveAgeMembers($record->id),
            'ecomDetails' => $ecomDetails,
            'quoteDocuments' => $quoteDocuments->toArray(),
            'documentTypes' => $documentTypes,
            'documentType' => $documentType,
            'cdnPath' => $cdnPath,
            'memberCategories' => $this->lookupService->getMemberCategories(),
            'emailStatuses' => $this->travelQuoteService->getEmailStatus(self::TYPE_ID, $record->id),
            'activities' => $activities,
            'payments' => $payments,
            'quoteRequest' => $paymentEntityModel,
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'paymentMethods' => $filteredPaymentMethods,
            'insuranceProviders' => $filteredInsuranceProviders,
            'isAdmin' => auth()->user()->isAdmin(),
            'customerAdditionalContacts' => $customerAdditionalContacts,
            'ecomTravelInsuranceQuoteUrl' => config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL'),
            'embeddedProducts' => $embeddedProducts,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::TravelManager),
            'message' => session('message'),
            'quoteType' => QuoteTypes::TRAVEL,
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'storageUrl' => storageUrl(),
            'permissions' => [
                'admin' => auth()->user()->hasAnyRole([RolesEnum::Admin]),
                'isManualAllocationAllowed' => auth()->user()->isAdmin() || auth()->user()->hasRole(RolesEnum::LeadPool) ? true : false,
                'notProductionApproval' => ! auth()->user()->hasRole(RolesEnum::PA),
                'travelAdvisor' => auth()->user()->hasRole(RolesEnum::TravelAdvisor),
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
                'displaySendPolicyButton' => $displaySendPolicyButton,
                'approve_payments' => auth()->user()->can(PermissionsEnum::ApprovePayments),
                'edit_payments' => auth()->user()->can(PermissionsEnum::PaymentsEdit),
                'canNotEditPayments' => auth()->user()->cannot(PermissionsEnum::PaymentsEdit),
                'auditable' => auth()->user()->can(PermissionsEnum::Auditable),
                'canNotApprovePayments' => auth()->user()->cannot(PermissionsEnum::ApprovePayments),
                'canEditQuote' => auth()->user()->can(strtolower($this->genericModel->modelType).'-quotes-edit') || (userHasProduct(quoteTypeCode::Travel) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS)),
                'create_payments' => auth()->user()->can(PermissionsEnum::PaymentsCreate) && $paymentEntityModel->plan && ! auth()->user()->hasRole(RolesEnum::PA),
                'isPA' => auth()->user()->hasRole(RolesEnum::PA),

            ],
            'enums' => [
                'travelQuoteEnum' => TravelQuoteEnum::asArray(),
            ],
            'sendUpdateEnum' => $sendUpdateEnum,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'nationalities' => $nationalities,
            'memberRelations' => $memberRelations,
            'industryType' => $industryType,
            'UBOsDetails' => $uboDetails,
            'UBORelations' => $uboRelations,
            'emirates' => $emirates,
            'bookPolicyDetails' => $bookPolicyDetails,
            'isNewPaymentStructure' => $isNewPaymentStructure,
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'paymentDocument' => $paymentDocument,
            'access' => $access,
            'lockStatusOfPolicyIssuanceSteps' => $lockStatusOfPolicyIssuanceSteps,
        ]);
    }

    public function create(Request $request)
    {
        $isRenewalUser = auth()->user()->isRenewalUser();

        $renewalAdvisors = $this->travelQuoteService->getRenewalAdvisors();

        $this->travelQuoteService->fillData();

        $fieldsToCreate = $this->travelQuoteService->getFieldsToCreate('skipProperties');
        $dropdownSource = $this->travelQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $customTitles = [];
        foreach ($fieldsToCreate as $property => $value) {
            if (str_contains($value, 'title')) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            } else {
                $customTitles[$property] = ucwords(str_replace('_', ' ', $property));
            }
        }

        $fields = [];
        foreach ($fieldsToCreate as $property => $value) {
            $value = array_diff(explode('|', $value), ['title']);
            $type = $value[0] == 'select' ? 'select' : $value[1] ?? 'text';
            $fields[$property] = [
                'type' => $type,
                'required' => in_array('required', $value),
                'readonly' => in_array('readonly', $value),
                'disabled' => in_array('disabled', $value),
                'value' => '',
                'label' => $customTitles[$property],
                'options' => $dropdownSource[$property] ?? [],
            ];
        }

        $model = $this->genericModel;

        return inertia('TravelQuote/Form', [
            'model' => json_encode($model->properties),
            'quotePlans' => null,
            'customTitles' => $customTitles,
            'fields' => $fields,
            'dropdownSource' => $dropdownSource,
            'renewalAdvisors' => $renewalAdvisors ?? [],
            'isRenewalUser' => $isRenewalUser,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTravelRequest $request)
    {
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;
        $record = $this->travelQuoteService->saveTravelQuote($request);

        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return redirect()->back()->with('message', $record->message)->withInput();
        }
        if (isset($response->quoteUID)) {
            return redirect('/quotes/travel/'.$response->quoteUID)->with('message', 'Quote created successfully.');

        }

        return redirect('/quotes/travel')->with('message', 'Quote created successfully.');
    }

    /**
     * @param  Request  $request
     * @return ResponseFactory|Response
     *
     * @throws RuntimeException
     */
    public function edit($id)
    {
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        if (! $record) {
            return abort(404);
        }
        $dropdownSource = $this->travelQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $fieldsToUpdate = $this->travelQuoteService->getFieldsToUpdate('skipProperties');
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
        $quotePlans = $this->travelQuoteService->listTravelQuotePlans($record->id);
        $travelDestinations = $this->travelQuoteService->getTravelDestinations($record->id);

        return inertia('TravelQuote/Form', [
            'quote' => $record,
            'quotePlans' => $quotePlans,
            'travelers' => $this->travelQuoteService->getMembersDetail($record->id),
            'modelType' => $this->genericModel->modelType,
            'genderOptions' => $this->crudService->getGenderOptions(),
            'dropdownSource' => $dropdownSource,
            'travelDestinations' => $travelDestinations,
            'model' => json_encode($this->genericModel->properties),
            'fields' => $fields,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTravelRequest $request, $id)
    {
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;

        $this->travelQuoteService->updateTravelQuote($request, $id);

        return redirect('/quotes/travel/'.$id)->with('message', 'Record updated successfully');
    }

    public function planDetails($quoteId, $planId)
    {
        $quotePlans = $this->travelQuoteService->getQuotePlans($quoteId);

        if (gettype($quotePlans) == 'string') {
            return response()->json([
                'message' => $quotePlans,
            ], 404);
        }
        $listQuotePlans = $quotePlans->quotes->plans;
        foreach ($listQuotePlans as $listQuotePlan) {
            if ($listQuotePlan->id == $planId) {
                $listQuotePlansMembers = $listQuotePlan->memberPremiumBreakdown;
                $listQuotePlanName = $listQuotePlan->name;
                $providerCode = $listQuotePlan->providerCode;
                $providerName = $listQuotePlan->providerName;
                $travelType = $listQuotePlan->travelType;
                $actualPremium = $listQuotePlan->actualPremium;
                $discountPremium = $listQuotePlan->discountPremium;
                $listQuotePlanBenefitsInclusions = $listQuotePlan->benefits->inclusion;
                $listQuotePlanBenefitstravelInconvenienceCover = $listQuotePlan->benefits->travelInconvenienceCover;
                $listQuotePlanBenefitsemergencyMedicalCover = $listQuotePlan->benefits->emergencyMedicalCover;
                $listQuotePlanBenefitsExclusions = $listQuotePlan->benefits->exclusion;
                $listQuotePlanBenefitsFeatures = $listQuotePlan->benefits->feature;
                $listQuotePlanBenefitsCovid19 = $listQuotePlan->benefits->covid19;
                $listQuotePlanBenefitsPolicyDetails = $listQuotePlan->policyWordings;
                $addons = $listQuotePlan->addons;
                $vat = $listQuotePlan->vat;
                $insurerQuoteNo = $listQuotePlan->insurerQuoteId;

                foreach ($listQuotePlanBenefitsPolicyDetails as $listQuotePlanBenefitsPolicyDetail) {
                    $listQuotePlanBenefitsPolicyDetailLink = $listQuotePlanBenefitsPolicyDetail->link;
                }
            }
        }

        $data = [
            'listQuotePlanName' => $listQuotePlanName ?? '',
            'providerCode' => $providerCode,
            'providerName' => $providerName,
            'travelType' => $travelType,
            'actualPremium' => $actualPremium,
            'discountPremium' => $discountPremium,
            'listQuotePlanBenefitsInclusions' => $listQuotePlanBenefitsInclusions,
            'listQuotePlanBenefitsExclusions' => $listQuotePlanBenefitsExclusions,
            'listQuotePlanBenefitsFeatures' => $listQuotePlanBenefitsFeatures,
            'listQuotePlanBenefitsCovid19' => $listQuotePlanBenefitsCovid19,
            'listQuotePlanBenefitsPolicyDetails' => $listQuotePlanBenefitsPolicyDetails,
            'listQuotePlanBenefitsPolicyDetailLink' => $listQuotePlanBenefitsPolicyDetailLink ?? '',
            'modelName' => self::TYPE,
            'listQuotePlansMembers' => $listQuotePlansMembers,
            'listQuotePlanBenefitstravelInconvenienceCover' => $listQuotePlanBenefitstravelInconvenienceCover,
            'listQuotePlanBenefitsemergencyMedicalCover' => $listQuotePlanBenefitsemergencyMedicalCover,
            'addons' => $addons,
            'id' => $planId,
            'vat' => $vat,
            'insurerQuoteNo' => $insurerQuoteNo,
        ];

        return response()->json($data, 200);
    }

    public function cardsView(Request $request)
    {
        $dropdownSourceService = app(DropdownSourceService::class);
        $leadStatuses = $dropdownSourceService->getDropdownSource('quote_status_id', self::TYPE_ID);

        $leadStatuses = $leadStatuses->filter(function ($item) {
            return $item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUOTED || $item->text == quoteStatusCode::FOLLOWEDUP || $item->text == quoteStatusCode::NEGOTIATION || $item->text == quoteStatusCode::PAYMENTPENDING;
        })->toArray();
        $leadStatuses = array_map(function ($item) use ($request) {
            $item['data'] = getDataAgainstStatus(self::TYPE, $item['id'], $request);

            return $item;
        }, $leadStatuses);

        return inertia('TravelQuote/Cards', [
            'quotes' => array_values($leadStatuses),
            'quoteType' => QuoteTypes::TRAVEL->value,
        ]);
    }

    public function uploadRenewals()
    {
        return inertia('TravelQuote/Upload');
    }

    /**
     * process upload and create import.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function renewalsUploadCreate(TravelRenewalsUploadRequest $request)
    {
        return $this->renewalQuoteService->travelRenewalsUploadCreate($request->validated());
    }

    public function travelPlanUpdateManualProcess(TravelPlanUpdateManualProcessRequest $request)
    {
        app(TravelQuoteService::class)->travelPlanModify($request->validated());
    }
}
