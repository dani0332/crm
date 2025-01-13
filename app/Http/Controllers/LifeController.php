<?php

namespace App\Http\Controllers;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TravelQuoteEnum;
use App\Http\Requests\StoreLifeRequest;
use App\Models\ApplicationStorage;
use App\Models\Emirate;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\NationalityRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
use App\Services\LifeQuoteService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\Reports\RenewalBatchReportService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\ResponseFactory;

class LifeController extends Controller
{
    use GenericQueriesAllLobs;

    protected $lifeQuoteService;
    protected $lookupService;
    protected $crudService;
    protected $genericModel;

    public const TYPE = quoteTypeCode::Life;
    public const TYPE_ID = QuoteTypeId::Life;

    /**
     * TravelController constructor.
     *
     * @param  LifeQuoteService  $service
     */
    public function __construct(LifeQuoteService $lifeQuoteService, LookupService $lookupService, CRUDService $crudService)
    {
        $this->lifeQuoteService = $lifeQuoteService;
        $this->genericModel = $this->lifeQuoteService->getGenericModel(self::TYPE);
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
        $gridData = $this->lifeQuoteService->getGridData($this->genericModel, $request);
        $lifeQuotes = $gridData->simplePaginate(10)->withQueryString();
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);
        $dropdownSource = $this->lifeQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $authorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();

        return inertia('LifeQuote/Index', [
            'quotes' => $lifeQuotes,
            'dropdownSource' => $dropdownSource,
            'advisors' => $advisors,
            'renewalBatches' => $renewalBatches,
            'authorizedDays' => intval($authorizedDays->value),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $isRenewalUser = auth()->user()->isRenewalUser();
        $renewalAdvisors = $this->lifeQuoteService->getRenewalAdvisors();
        $this->lifeQuoteService->fillData();

        $fieldsToCreate = $this->lifeQuoteService->getFieldsToCreate('skipProperties');
        $dropdownSource = $this->lifeQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
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
            $value = explode('|', $value);
            $typeOfField = array_diff($value, ['title', 'input', 'required']);
            $typeOfField = array_shift($typeOfField);

            if (in_array('static', $value)) {
                $options = $this->lifeQuoteService->getStaticFields($value);
                $dropdownSource[$property] = $options;
                $typeOfField = 'select';
            }

            $fields[$property] = [
                'type' => $typeOfField,
                'required' => in_array('required', $value),
                'readonly' => in_array('readonly', $value),
                'disabled' => in_array('disabled', $value),
                'value' => '',
                'label' => $customTitles[$property],
                'options' => $dropdownSource[$property] ?? [],
            ];
        }

        $model = $this->genericModel;

        return inertia('LifeQuote/Form', [
            'model' => json_encode($model->properties),
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
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLifeRequest $request)
    {
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;
        $record = $this->lifeQuoteService->saveLifeQuote($request);

        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return redirect()->back()->with('message', $record->message)->withInput();
        }

        return redirect('/personal-quotes/life')->with('message', 'Quote created successfully.');
    }

    public function show($uuid)
    {
        /* Start - Temporarily adding for correcting historic data  */
        $quote = $this->lifeQuoteService->getEntity($uuid);
        abort_if(! $quote, 404);
        (new PaymentRepository)->updatePriceVatApplicableAndVat($quote, QuoteTypes::LIFE->value);
        /* End - Temporarily adding for correcting historic data  */

        $paymentEntityModel = $this->{strtolower($this->genericModel->modelType).'QuoteService'}->getEntityPlain($quote->id);
        $payments = $paymentEntityModel->payments;

        $quoteType = strtolower($this->genericModel->modelType);
        $allowedDuplicateLOB = $this->crudService->getAllowedDuplicateLOB($quoteType, $quote->code);
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);
        $advisors->load(['roles']);

        $this->lifeQuoteService->fillData();

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::LIFE->id())->get();
        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        })->values();
        $quoteStatuses = app(CentralService::class)->lockTransactionStatus($quote, QuoteTypes::LIFE->id(), $quoteStatuses);

        if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }

        $fields = $this->lifeQuoteService->fieldsToDisplay($this->lifeQuoteService->getFieldsToShow(), $quote);
        $customTitles = [];
        foreach ($fields as $property => $value) {
            if (in_array('title', $value)) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            } else {
                $customTitles[$property] = ucwords(str_replace('_', ' ', $property));
            }
        }

        $isQuoteDocumentEnabled = $this->lifeQuoteService->quoteDocumentEnabled($this->genericModel->modelType);
        $quoteDocuments = $this->lifeQuoteService->getQuoteDocuments($this->genericModel->modelType, $quote->id);
        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypeId::Life);
        $amlStatusName = AMLStatusCode::getName($quote->aml_status);

        $customerAdditionalContacts = $this->lifeQuoteService->getAdditionalContacts($quote->customer_id, $quote->mobile_no);
        $activities = $this->lifeQuoteService->getActivityByLeadId($quote->id, strtolower($this->genericModel->modelType));
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::LIFE->id(), $quote->id);
        $nationalities = NationalityRepository::withActive()->get();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $membersDetails = CustomerMembersRepository::getBy($quote->id, QuoteTypes::LIFE->name);
        $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();
        $uboDetails = CustomerMembersRepository::getBy($quote->id, QuoteTypes::LIFE->name, CustomerTypeEnum::Entity);
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::LIFE->id());
        $bookPolicyDetails = $this->bookPolicyPayload($quote, QuoteTypes::LIFE->value, $payments, $quoteDocuments);
        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];

        $hasPolicyIssuedStatus = $this->crudService->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = $this->lookupService->getSendUpdateOptions(QuoteTypeId::Life);
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(QuoteTypes::LIFE->value, $quote);
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quote);

        return inertia('LifeQuote/Show', [
            'documentTypes' => $documentTypes,
            'storageUrl' => storageUrl(),
            'quoteType' => QuoteTypes::LIFE,
            'quoteTypeId' => QuoteTypeId::Life,
            'quoteStatuses' => $quoteStatuses,
            'quote' => $quote,
            'amlStatusName' => $amlStatusName,
            'record' => $quote,
            'activities' => $activities,
            'advisors' => $advisors,
            'allowedDuplicateLOB' => $allowedDuplicateLOB,
            'customerAdditionalContacts' => $customerAdditionalContacts,
            'lostReasons' => $this->lookupService->getLostReasons(),
            'modelType' => QuoteTypes::LIFE,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::LifeManager),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'nationalities' => $nationalities,
            'memberRelations' => $memberRelations,
            'membersDetails' => $membersDetails,
            'industryType' => $industryType,
            'emirates' => $emirates,
            'UBOsDetails' => $uboDetails,
            'UBORelations' => $uboRelations,
            'paymentMethods' => (new LookupService)->getPaymentMethods(),
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'quoteRequest' => $paymentEntityModel,
            'payments' => $payments,
            'insuranceProviders' => $insuranceProviders,
            'permissions' => [
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
            ],
            'enums' => [
                'travelQuoteEnum' => TravelQuoteEnum::asArray(),
            ],
            'bookPolicyDetails' => $bookPolicyDetails,
            'vatPercentage' => $vatPercentage,
            'isNewPaymentStructure' => app(SplitPaymentService::class)->isNewPaymentStructure($payments),
            'sendUpdateEnum' => $sendUpdateEnum,
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'paymentDocument' => $paymentDocument,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        $dropdownSource = $this->lifeQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $fieldsToUpdate = $this->lifeQuoteService->getFieldsToUpdate('skipProperties');
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
            $value = explode('|', $value);
            $typeOfField = array_diff($value, ['title', 'input', 'required']);
            $typeOfField = array_shift($typeOfField);

            if (in_array('static', $value)) {
                $options = $this->lifeQuoteService->getStaticFields($value, $property);
                $dropdownSource[$property] = $options;
                $typeOfField = 'select';
            }

            $fields[$property] = [
                'type' => $typeOfField,
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

        return inertia('LifeQuote/Form', [
            'quote' => $record,
            'modelType' => $this->genericModel->modelType,
            'genderOptions' => $this->crudService->getGenderOptions(),
            'dropdownSource' => $dropdownSource,
            'model' => json_encode($this->genericModel->properties),
            'fields' => $fields,
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
        $modelPropertiesList = json_decode($request->all()['model'], true);

        $validateArray = [];

        $modelSkipPropertiesList = json_decode($request->get('modelSkipProperties'), true);
        foreach ($modelPropertiesList as $property => $value) {
            if (strpos($value, 'required') && $property != 'id' && $property != 'code' && $property != 'email' && $property != 'mobile_no' && $modelSkipPropertiesList != null && ! strpos($modelSkipPropertiesList['update'], $property)) {
                $validateArray[$property] = 'required';
            }
        }
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;
        $this->validate($request, $validateArray);
        $this->crudService->updateModelByType(json_decode($request->modelType, true), $request, $id);

        return redirect('/personal-quotes/life'.'/'.$id)->with('success', json_decode($request->modelType, true).' has been updated');
    }

    public function cardsView(Request $request)
    {
        $dropdownSourceService = app(DropdownSourceService::class);
        $leadStatuses = $dropdownSourceService->getDropdownSource('quote_status_id', self::TYPE_ID);
        $leadStatuses = $leadStatuses->filter(function ($item) {
            return $item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUOTED || $item->text == quoteStatusCode::FOLLOWEDUP || $item->text == quoteStatusCode::NEGOTIATION;
        })->toArray();

        $leadStatuses = array_map(function ($item) use ($request) {
            $item['data'] = getDataAgainstStatus(self::TYPE, $item['id'], $request);

            return $item;
        }, $leadStatuses);

        return inertia('LifeQuote/Cards', [
            'quotes' => array_values($leadStatuses),
            'quoteType' => self::TYPE,
        ]);
    }
}
