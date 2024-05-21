<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Requests\StoreBusinessQuoteRequest;
use App\Http\Requests\UpdateBusinessQuoteRequest;
use App\Models\ApplicationStorage;
use App\Models\BusinessQuote;
use App\Models\Emirate;
use App\Models\Entity;
use App\Models\KycLog;
use App\Models\Nationality;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Services\AMLService;
use App\Services\BusinessQuoteService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\RolePermissionConditions;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BusinessQuoteController extends Controller
{
    protected $businessQuoteService;
    protected $crudService;
    protected $lookupService;
    protected $genericModel;
    protected $dropdownSourceService;

    public const TYPE = quoteTypeCode::Business;
    public const TYPE_ID = QuoteTypeId::Business;

    use GenericQueriesAllLobs, RolePermissionConditions;

    public function __construct(
        BusinessQuoteService $businessQuoteService,
        CRUDService $crudService,
        LookupService $lookupService,
        DropdownSourceService $dropdownSourceService
    ) {
        $this->businessQuoteService = $businessQuoteService;
        $this->genericModel = $this->businessQuoteService->getGenericModel(self::TYPE);
        $this->crudService = $crudService;
        $this->lookupService = $lookupService;
        $this->dropdownSourceService = $dropdownSourceService;
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
        $isManagerORDeputy = auth()->user()->isManagerORDeputy();
        $isManualAllocationAllowed = auth()->user()->isAdmin() ? true : $isManagerORDeputy;

        return inertia('CorpLineQuote/Index', compact('quotes', 'dropdownSource', 'isManualAllocationAllowed'));
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
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create(Request $request)
    {
        $isRenewalUser = auth()->user()->isRenewalUser();

        $renewalAdvisors = $this->businessQuoteService->getRenewalAdvisors();
        $this->businessQuoteService->fillData();
        $dropdownSource = $this->businessQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);

        $model = $this->genericModel;

        return inertia('CorpLineQuote/Form', [
            'quote' => new BusinessQuote(),
            'dropdownSource' => $dropdownSource,
            'renewalAdvisors' => $renewalAdvisors ?? [],
            'isRenewalUser' => $isRenewalUser,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBusinessQuoteRequest $request)
    {
        $record = $this->businessQuoteService->saveBusinessQuote($request);
        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return redirect()->back()->with('message', $record->message)->withInput();
        } else {
            if (! isset($record->quoteUID)) {
                return redirect('quotes/business')->with('success', 'Lead has been stored');
            } else {
                return redirect('quotes/business/'.$record->quoteUID)->with('success', 'Lead has been stored');
            }
        }
    }

    /**
     * @param  $uuid
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($id)
    {
        $quoteType = strtolower($this->genericModel->modelType);
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        abort_if(! $record, 404);
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
        $latestKycLog = KycLog::withTrashed()->where('quote_request_id', $record->id)->latest()->first();
        @[$documentTypes, $documentTypeCodes] = app(QuoteDocumentService::class)->getDocumentTypes(self::TYPE_ID, $record?->business_type_of_insurance_id, $latestKycLog?->search_type);
        $activities = $this->businessQuoteService->getActivityByLeadId($record->id, strtolower($this->genericModel->modelType));
        $customerAdditionalContacts = $this->businessQuoteService->getAdditionalContacts($record->customer_id, $record->mobile_no);

        $paymentEntityModel = $this->{strtolower($this->genericModel->modelType).'QuoteService'}->getEntityPlain($record->id);
        $payments = $paymentEntityModel->payments;
        $paymentMethods = $this->lookupService->getPaymentMethods();

        $isNewPaymentStructure = app(SplitPaymentService::class)->isNewPaymentStructure($payments);
        if ($isNewPaymentStructure) {
            $filteredPaymentMethods = $this->lookupService->getPaymentMethods();
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

        if (AMLService::checkAMLStatusFailed(self::TYPE_ID, $record->id)) {
            $dropdownSource['quote_status_id'] = collect($dropdownSource['quote_status_id'])->filter(function ($value) {
                return $value['id'] != QuoteStatusEnum::TransactionApproved;
            })->values();
        }

        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::Business);
        $companyType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        $UBODetails = CustomerMembersRepository::getBy($record->id, QuoteTypes::BUSINESS->name, CustomerTypeEnum::Entity);
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        $UBORelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();

        $isAmlClearedForPayment = app(CentralService::class)->amlClearedFromLog($record->id, QuoteTypes::BUSINESS->name);

        $filteredInsuranceProviders = [];
        if (! empty($insuranceProviders)) {

            $filteredInsuranceProviders = $insuranceProviders->map(function ($paymentMethod) {
                return [
                    'value' => $paymentMethod->id,
                    'label' => $paymentMethod->text,
                ];
            })->sortBy('label')->values();
        }
        $payments->load(['paymentStatus', 'paymentStatusLog', 'paymentMethod', 'insuranceProvider']);

        $payments->each(function ($payment) {
            $allow = $payment->payment_status_id != PaymentStatusEnum::CAPTURED && $payment->payment_status_id != PaymentStatusEnum::AUTHORISED && ! auth()->user()->hasRole(RolesEnum::PA);
            $payment->copy_link_button = $allow && optional($payment->paymentMethod)->code == PaymentMethodsEnum::CreditCard && $payment->payment_status_id != PaymentStatusEnum::PAID;
            $payment->edit_button = $allow && $payment->payment_status_id != PaymentStatusEnum::PAID;
            $payment->approve_button = optional($payment->paymentMethod)->code != PaymentMethodsEnum::CreditCard && $payment->payment_status_id != PaymentStatusEnum::PAID && $payment->payment_status_id != PaymentStatusEnum::CAPTURED
                && ! auth()->user()->hasRole(RolesEnum::PA);

            $payment->approved_button = $payment->payment_status_id == PaymentStatusEnum::PAID;
        });

        $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';

        $countries = Nationality::all();
        $amlQuoteStatus = $this->crudService->checkAmlQuoteStatus($record->quote_status_id);
        $entities = Entity::all();
        $legalStructure = $this->lookupService->getLegalStructure();
        $idDocumentType = $this->lookupService->getEntityDocumentTypes();
        $issuancePlace = $this->lookupService->getIssuancePlaces();
        $issuanceAuthorities = $this->lookupService->getIssuanceAuthorities();
        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = $this->crudService->hasAtleastOneStatusPolicyIssued(QuoteTypes::BUSINESS->id(), $record->id);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::BUSINESS->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($record->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = $this->crudService->hasAtleastOneStatusPolicyIssued(QuoteTypes::BUSINESS->id(), $record->id);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::BUSINESS->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($record->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        $bookPolicyDetails = $this->bookPolicyPayload($record, QuoteTypes::BUSINESS->value, $payments, $quoteDocuments);

        return inertia('CorpLineQuote/Show', [
            'storageUrl' => storageUrl(),
            'amlQuoteStatus' => $amlQuoteStatus,
            'countryList' => $countries,
            'entities' => $entities,
            'legalStructure' => $legalStructure,
            'idDocumentType' => $idDocumentType,
            'issuancePlace' => $issuancePlace,
            'issuanceAuthorities' => $issuanceAuthorities,
            'quoteType' => quoteTypeCode::Business,
            'quote' => $record,
            'quoteDetails' => $quoteDetails,
            'modelType' => $this->genericModel->modelType,
            'quoteTypeId' => QuoteTypeId::Business,
            'dropdownSource' => $dropdownSource,
            'leadStatuses' => $dropdownSource['quote_status_id'],
            'advisors' => $advisors,
            'renewalAdvisors' => $renewalAdvisors,
            'allowedDuplicateLOB' => $allowedDuplicateLOB,
            'assignmentTypes' => $assignmentTypes,
            'genderOptions' => $this->crudService->getGenderOptions(),
            'lostReasons' => $this->lookupService->getLostReasons(),
            'quoteDocuments' => array_values($quoteDocuments->toArray()),
            'documentTypes' => $documentTypes,
            'cdnPath' => $cdnPath,
            'memberCategories' => $this->lookupService->getMemberCategories(),
            'activities' => $activities,
            'isAdmin' => auth()->user()->isAdmin(),
            'customerAdditionalContacts' => $customerAdditionalContacts,
            'ecomTravelInsuranceQuoteUrl' => config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL'),
            'payments' => $payments,
            'quoteRequest' => $paymentEntityModel,
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'paymentMethods' => $filteredPaymentMethods,
            'insuranceProviders' => $filteredInsuranceProviders,
            'insuranceProvidersAll' => $insuranceProviders,
            'permissions' => [
                'admin' => auth()->user()->hasAnyRole([RolesEnum::Admin]),
                'isManualAllocationAllowed' => auth()->user()->isAdmin() || auth()->user()->hasRole(RolesEnum::LeadPool) ? true : false,
                'notProductionApproval' => ! auth()->user()->hasRole(RolesEnum::PA),
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
                'displaySendPolicyButton' => $displaySendPolicyButton,
                'approve_payments' => auth()->user()->can(PermissionsEnum::ApprovePayments),
                'edit_payments' => auth()->user()->can(PermissionsEnum::PaymentsEdit),
                'canNotEditPayments' => auth()->user()->cannot(PermissionsEnum::PaymentsEdit),
                'auditable' => auth()->user()->can(PermissionsEnum::Auditable),
                'canNotApprovePayments' => auth()->user()->cannot(PermissionsEnum::ApprovePayments),
                'canEditQuote' => auth()->user()->can('corpline-quotes-edit'),
                'create_payments' => auth()->user()->can(PermissionsEnum::PaymentsCreate) && $paymentEntityModel->plan && ! auth()->user()->hasRole(RolesEnum::PA),
                'isPA' => auth()->user()->hasRole(RolesEnum::PA),

            ],
            'typeCode' => quoteTypeCode::CORPLINE,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'companyTypes' => $companyType,
            'UBOsDetails' => $UBODetails,
            'UBORelations' => $UBORelations,
            'nationalities' => $nationalities,
            'emirates' => $emirates,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::CorplineManager),
            'vatPercentage' => $vatPercentage,
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'isNewPaymentStructure' => $isNewPaymentStructure,
            'isAmlClearedForPayment' => $isAmlClearedForPayment,
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'sendUpdateEnum' => $sendUpdateEnum,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'documentTypeCodes' => $documentTypeCodes,
            'record' => $record,
            'bookPolicyDetails' => $bookPolicyDetails,
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
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

        return inertia('CorpLineQuote/Form', [
            'quote' => $record,
            'modelType' => $this->genericModel->modelType,
            'dropdownSource' => $dropdownSource,
            'leadStatuses' => $dropdownSource['quote_status_id'],
            'genderOptions' => $this->crudService->getGenderOptions(),
            'lostReasons' => $this->lookupService->getLostReasons(),
            'isAdmin' => auth()->user()->isAdmin(),
            'permissions' => [
                'admin' => auth()->user()->hasAnyRole([RolesEnum::Admin]),
                'notProductionApproval' => ! auth()->user()->hasRole(RolesEnum::PA),
                'auditable' => auth()->user()->can(PermissionsEnum::Auditable),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBusinessQuoteRequest $request, $id)
    {
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;

        $this->crudService->updateModelByType('business', $request, $id);

        return redirect('/quotes/business/'.$id)->with('success', 'Business quote has been updated');
    }

    public function cardsView(Request $request)
    {
        $quotes = [];
        $leadStatuses = $this->dropdownSourceService->getDropdownSource('quote_status_id', QuoteTypeId::Business);
        $leadStatuses = $leadStatuses->filter(function ($item) {
            return $item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUOTED || $item->text == quoteStatusCode::PAYMENTPENDING || $item->text == quoteStatusCode::QUALIFIED || $item->text == quoteStatusCode::APPLICATION_PENDING || $item->text == quoteStatusCode::MISSING_DOCUMENTS || $item->text == quoteStatusCode::PENDINGUW || $item->text == quoteStatusCode::PLOICY_DOCUMENTS_PENDING;
        })->toArray();

        $leadStatuses = array_map(function ($item) {
            $item['data'] = getDataAgainstStatus('Business', $item['id']);

            return $item;
        }, $leadStatuses);

        return inertia('CorpLineQuote/Cards', [
            'quotes' => array_values($leadStatuses),
        ]);
    }
}
