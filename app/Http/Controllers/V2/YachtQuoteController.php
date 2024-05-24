<?php

namespace App\Http\Controllers\V2;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentTooltip;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BikeQuoteRequest;
use App\Http\Requests\YachtQuoteRequest;
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
use App\Repositories\QuoteStatusRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Repositories\UserRepository;
use App\Repositories\YachtQuoteRepository;
use App\Services\AMLService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;

class YachtQuoteController extends Controller
{
    use GenericQueriesAllLobs;
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
     * @param  $quoteTypeCode
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
        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(QuoteTypes::YACHT->value, $quote);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::YACHT->id())->get();
        $membersDetail = CustomerMembersRepository::getBy($quote->id, QuoteTypes::YACHT->name);
        $quote->load('documents.createdBy:id,name,email');

        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::YACHT->id())->active()->get();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::YACHT->id());
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::YACHT->value);

        $isAmlClearedForPayment = app(CentralService::class)->amlClearedFromLog($quote->id, QuoteTypes::YACHT->name);

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::YACHT->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        if (AMLService::checkAMLStatusFailed(QuoteTypes::YACHT->id(), $quote->id)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return $value['id'] != QuoteStatusEnum::TransactionApproved;
            })->values();
        }

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::YACHT->id(), $quote->id);
        $lookupService = app(LookupService::class);
        $industryType = $lookupService->getCompanyTypes();
        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = $lookupService->getSendUpdateOptions(QuoteTypes::YACHT->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        $isQuoteDocumentEnabled = app(QuoteDocumentService::class)->isEnabled(QuoteTypes::YACHT->value);
        $quoteDocuments = (new QuoteDocumentService())->getQuoteDocuments(QuoteTypes::YACHT->value, $quote->id);
        $bookPolicyDetails = $this->bookPolicyPayload($quote, QuoteTypes::YACHT->value, $quote->payments, $quoteDocuments);

        return inertia('YachtQuote/Show', [
            'quoteType' => QuoteTypes::YACHT,
            'quote' => $quote,
            'activities' => $activities,
            'lostReasons' => $lostReasons,
            'quoteTypeId' => QuoteTypes::YACHT->id(),
            'advisors' => $advisors,
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
            'vatPercentage' => $vatPercentage,
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'isNewPaymentStructure' => app(SplitPaymentService::class)->isNewPaymentStructure($quote->payments),
            'isAmlClearedForPayment' => $isAmlClearedForPayment,
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'sendUpdateEnum' => $sendUpdateEnum,
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'record' => $quote,
            'permissions' => [
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
            ],
            'bookPolicyDetails' => $bookPolicyDetails,
            'payments' => $quote->payments->toArray() ?? [],
        ]);
    }

    /**
     * @param  $quoteTypeCode
     * @param  $quoteId
     * @param  BikeQuoteRequest  $request
     * @return void
     */
    public function update($uuid, YachtQuoteRequest $request)
    {
        YachtQuoteRepository::update($uuid, $request->validated());

        return redirect('personal-quotes/yacht/'.$uuid)->with('message', 'Quote updated successfully');
    }
}
