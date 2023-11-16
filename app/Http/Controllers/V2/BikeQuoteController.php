<?php

namespace App\Http\Controllers\V2;

use App\Enums\AmlSearchType;
use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BikeQuoteRequest;
use App\Models\Emirate;
use App\Models\Entity;
use App\Models\Nationality;
use App\Repositories\ActivityRepository;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteMemberDetailsRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
use App\Services\CRUDService;
use App\Services\LookupService;
use App\Traits\GenericQueriesAllLobs;

class BikeQuoteController extends Controller
{
    protected $crudService;
    protected $lookupService;
    use GenericQueriesAllLobs;

    public function __construct(CRUDService $crudService, LookupService $lookupService)
    {
        $this->crudService = $crudService;
        $this->lookupService = $lookupService;
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $personalQuotes = BikeQuoteRepository::getData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::BIKE->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::BIKE->id())->get();

        return inertia('BikeQuote/Index', [
            'quotes' => $personalQuotes,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $data = BikeQuoteRepository::getFormOptions();

        return inertia('BikeQuote/Form', $data);
    }

    /**
     * @param $quoteTypeCode
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(BikeQuoteRequest $request)
    {
        $response = BikeQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect('personal-quotes/bike/'.$response->quoteUID)->with('message', 'Quote created successfully');
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $data = BikeQuoteRepository::getFormOptions();

        $quote = BikeQuoteRepository::getBy('uuid', $uuid);

        return inertia(
            'BikeQuote/Form',
            array_merge($data, [
                'quote' => $quote,
            ])
        );
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {
        $quote = BikeQuoteRepository::getBy('uuid', $uuid);

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::BIKE->id())->get();
        $membersDetail = QuoteMemberDetailsRepository::getBy('quote_request_id', $quote->id, QuoteTypes::BIKE->id());
        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::BIKE->id())->get();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::BIKE->id());
        //dd($insuranceProviders->toArray());
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::BIKE->value);

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::BIKE->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::BIKE->id(), $quote->id);
        $uboDetails = QuoteMemberDetailsRepository::getBy('quote_request_id', $quote->id, QuoteTypeId::Bike, CustomerTypeEnum::Entity);
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();

        if ($quote->quote_status_id !== QuoteStatusEnum::AMLScreeningCleared) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return $value['id'] != QuoteStatusEnum::TransactionApproved;
            })->values();
        }

        $amlQuoteStatus = $this->crudService->checkAmlQuoteStatus($quote->quote_status_id);
        $countries = Nationality::all();
        $entities = $residentialStatus = $legalStructure = $idDocumentType = $modeOfContact = $employmentSectors = $companyPosition = $issuancePlace = $issuanceAuthorities = null;
        if ($quote->customer_type == AmlSearchType::ENTITY) {
            $entities = Entity::all();
            $legalStructure = $this->lookupService->getLegalStructure();
            $idDocumentType = $this->lookupService->getEntityDocumentTypes();
            $issuancePlace = $this->lookupService->getIssuancePlaces();
            $issuanceAuthorities = $this->lookupService->getIssuanceAuthorities();
        } else {
            $idDocumentType = $this->lookupService->getIndividualDocumentTypes();
            $modeOfContact = $this->lookupService->getModeOfContact();
            $employmentSectors = $this->lookupService->getEmploymentSector();
            $residentialStatus = $this->lookupService->getResidentialStatus();
            $companyPosition = $this->lookupService->getCompanyPosition();
        }

        return inertia('BikeQuote/Show', [
            'amlQuoteStatus' => $amlQuoteStatus,
            'countryList' => $countries,
            'entities' => $entities,
            'legalStructure' => $legalStructure,
            'idDocumentType' => $idDocumentType,
            'issuancePlace' => $issuancePlace,
            'issuanceAuthorities' => $issuanceAuthorities,
            'modeOfContact' => $modeOfContact,
            'employmentSectors' => $employmentSectors,
            'residentialStatus' => $residentialStatus,
            'companyPosition' => $companyPosition,
            'quoteType' => QuoteTypes::BIKE,
            'quote' => $quote,
            'activities' => $activities,
            'lostReasons' => $lostReasons,
            'advisors' => $advisors,
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
            'documentTypes' => $documentTypes,
            'quoteStatuses' => $quoteStatuses,
            'paymentMethods' => $paymentMethods,
            'insuranceProviders' => $insuranceProviders,
            'personalPlans' => $personalPlans,
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'storageUrl' => storageUrl(),
            'modelType' => QuoteTypes::BIKE,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::BikeManager),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'membersDetails' => $membersDetail,
            'memberRelations' => $memberRelations,
            'nationalities' => $nationalities,
            'emirates' => $emirates,
            'UBOsDetails' => $uboDetails,
            'UBORelations' => $uboRelations,
        ]);
    }

    /**
     * @param $quoteTypeCode
     * @param $quoteId
     * @return void
     */
    public function update($uuid, BikeQuoteRequest $request)
    {
        BikeQuoteRepository::update($uuid, $request->validated());

        return redirect('personal-quotes/bike/'.$uuid)->with('message', 'Quote updated successfully');
    }
}
