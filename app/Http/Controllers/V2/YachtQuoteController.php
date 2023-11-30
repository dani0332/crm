<?php

namespace App\Http\Controllers\V2;

use App\Enums\AmlSearchType;
use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BikeQuoteRequest;
use App\Http\Requests\YachtQuoteRequest;
use App\Models\Emirate;
use App\Models\Entity;
use App\Models\Nationality;
use App\Repositories\ActivityRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
use App\Repositories\YachtQuoteRepository;
use App\Services\CRUDService;
use App\Services\LookupService;

class YachtQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $personalQuotes = YachtQuoteRepository::getData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::YACHT->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::YACHT->id())->get();

        return inertia('YachtQuote/Index', [
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
        return inertia('YachtQuote/Form');
    }

    /**
     * @param $quoteTypeCode
     * @param  BikeQuoteRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(YachtQuoteRequest $request)
    {
        $response = YachtQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect('personal-quotes/yacht/'.$response->quoteUID)->with('message', 'Quote created successfully');
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $quote = YachtQuoteRepository::getBy('uuid', $uuid);

        return inertia('YachtQuote/Form', ['quote' => $quote]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {
        $quote = YachtQuoteRepository::getBy('uuid', $uuid);

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::YACHT->id())->get();
        $membersDetail = CustomerMembersRepository::getBy('quote_id', $quote->id, QuoteTypes::YACHT->name);
        $quote->load('documents.createdBy');

        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::YACHT->id())->get();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::YACHT->id());
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::YACHT->value);

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::YACHT->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        if ($quote->quote_status_id !== QuoteStatusEnum::AMLScreeningCleared) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return $value['id'] != QuoteStatusEnum::TransactionApproved;
            })->values();
        }

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::YACHT->id(), $quote->id);
        $amlQuoteStatus = app(CRUDService::class)->checkAmlQuoteStatus($quote->quote_status_id);
        $countries = Nationality::all();
        $entities = $residentialStatus = $legalStructure = $idDocumentType = $modeOfContact = $employmentSectors = $companyPosition = $issuancePlace = $issuanceAuthorities = $industryType = null;
        $lookupService = app(LookupService::class);
        if ($quote->customer_type == AmlSearchType::ENTITY) {
            $entities = Entity::all();
            $legalStructure = $lookupService->getLegalStructure();
            $idDocumentType = $lookupService->getEntityDocumentTypes();
            $issuancePlace = $lookupService->getIssuancePlaces();
            $issuanceAuthorities = $lookupService->getIssuanceAuthorities();
            $industryType = $lookupService->getCompanyTypes();
        } else {
            $idDocumentType = $lookupService->getIndividualDocumentTypes();
            $modeOfContact = $lookupService->getModeOfContact();
            $employmentSectors = $lookupService->getEmploymentSector();
            $residentialStatus = $lookupService->getResidentialStatus();
            $companyPosition = $lookupService->getCompanyPosition();
        }

        return inertia('YachtQuote/Show', [
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
            'quoteType' => QuoteTypes::YACHT,
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
            'modelType' => QuoteTypes::YACHT,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::YachtManager),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'membersDetails' => $membersDetail,
            'memberRelations' => $memberRelations,
            'nationalities' => $nationalities,
            'industryType' => $industryType,
            'emirates' => $emirates,
        ]);
    }

    /**
     * @param $quoteTypeCode
     * @param $quoteId
     * @param  BikeQuoteRequest  $request
     * @return void
     */
    public function update($uuid, YachtQuoteRequest $request)
    {
        YachtQuoteRepository::update($uuid, $request->validated());

        return redirect('personal-quotes/yacht/'.$uuid)->with('message', 'Quote updated successfully');
    }
}
