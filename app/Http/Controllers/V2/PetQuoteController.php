<?php

namespace App\Http\Controllers\V2;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentTooltip;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\PetQuoteRequest;
use App\Models\ApplicationStorage;
use App\Models\Emirate;
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
use App\Repositories\PetQuoteRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Repositories\UserRepository;
use App\Services\AMLService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;

class PetQuoteController extends Controller
{
    use GenericQueriesAllLobs;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $personalQuotes = PetQuoteRepository::getData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::PET->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::PET->id())->get();

        return inertia('PetQuote/Index', [
            'quotes' => $personalQuotes,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = PetQuoteRepository::getFormOptions();

        return inertia('PetQuote/Form', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function store(PetQuoteRequest $request)
    {
        $response = PetQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('pet-quotes-show', $response->quoteUID))->with('message', 'Quote is created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($uuid)
    {
        $quote = PetQuoteRepository::getBy('uuid', $uuid);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::PET->id())->get();
        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(QuoteTypes::PET->value, $quote);
        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::PET->id())->active()->get();
        $membersDetail = CustomerMembersRepository::getBy($quote->id, QuoteTypes::PET->name);
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::PET->id());
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::PET->value);
        $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        $uboDetails = CustomerMembersRepository::getBy($quote->id, QuoteTypes::PET->name, CustomerTypeEnum::Entity);
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();

        $isAmlClearedForPayment = app(CentralService::class)->amlClearedFromLog($quote->id, QuoteTypes::PET->name);

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::PET->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::PET->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $duplicateAllowedLobs = (new CentralService())->duplicateAllowedLobsList(QuoteTypes::PET->value, $quote->code);
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::PET->id(), $quote->id);
        if (AMLService::checkAMLStatusFailed(QuoteTypes::PET->id(), $quote->id)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return $value['id'] != QuoteStatusEnum::TransactionApproved;
            })->values();
        }
        $isQuoteDocumentEnabled = app(QuoteDocumentService::class)->isEnabled(QuoteTypes::PET->value);
        $quoteDocuments = (new QuoteDocumentService())->getQuoteDocuments(QuoteTypes::PET->value, $quote->id);
        $bookPolicyDetails = $this->bookPolicyPayload($quote, QuoteTypes::PET->value, $quote->payments, $quoteDocuments);

        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;

        return inertia('PetQuote/Show', [
            'quoteType' => QuoteTypes::PET,
            'quote' => $quote,
            'activities' => $activities,
            'lostReasons' => $lostReasons,
            'advisors' => $advisors,
            'documentTypes' => $documentTypes,
            'quoteStatuses' => $quoteStatuses,
            'paymentMethods' => $paymentMethods,
            'insuranceProviders' => $insuranceProviders,
            'personalPlans' => $personalPlans,
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'storageUrl' => storageUrl(),
            'duplicateAllowedLobs' => $duplicateAllowedLobs,
            'modelType' => QuoteTypes::PET,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::PetManager),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'membersDetails' => $membersDetail,
            'memberRelations' => $memberRelations,
            'nationalities' => $nationalities,
            'quoteTypeId' => QuoteTypeId::Pet,
            'industryType' => $industryType,
            'emirates' => $emirates,
            'UBOsDetails' => $uboDetails,
            'UBORelations' => $uboRelations,
            'vatPercentage' => $vatPercentage,
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'record' => fn () => $quote,
            'permissions' => [
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
            ],
            'bookPolicyDetails' => $bookPolicyDetails,
            'payments' => $quote->payments->toArray() ?? [],
            'isNewPaymentStructure' => app(SplitPaymentService::class)->isNewPaymentStructure($quote->payments),
            'isAmlClearedForPayment' => $isAmlClearedForPayment,
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'sendUpdateEnum' => $sendUpdateEnum,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'linkedQuoteDetails' => $linkedQuoteDetails,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        $data = PetQuoteRepository::getFormOptions();
        $quote = PetQuoteRepository::getBy('uuid', $uuid);

        return inertia('PetQuote/Form', array_merge($data, [
            'quote' => $quote,
        ]));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PetQuoteRequest $request, $uuid)
    {
        PetQuoteRepository::update($uuid, $request->validated());

        return redirect(route('pet-quotes-show', $uuid))->with('message', 'Quote is updated successfully.');
    }
}
