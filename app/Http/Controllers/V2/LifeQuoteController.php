<?php

namespace App\Http\Controllers\V2;

use App\Enums\ApplicationStorageEnums;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\LifeQuoteRequest;
use App\Models\ApplicationStorage;
use App\Models\Emirate;
use App\Models\LifeQuote;
use App\Repositories\ActivityRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\NationalityRepository;
use App\Repositories\LifeQuoteRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
use App\Services\BaseService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\Life\LifeQuoteService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\Reports\RenewalBatchReportService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;

class LifeQuoteController extends Controller
{
    use GenericQueriesAllLobs;

    private $lifeQuoteService;

    public function __construct(LifeQuoteService $lifeQuoteService)
    {
        $this->lifeQuoteService = $lifeQuoteService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $lifeQuotes = $this->lifeQuoteService->getLifeQuoteData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::LIFE->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::LIFE->id())->get();
        $authorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();

        return inertia('LifeQuote/Index', [
            'quotes' => $lifeQuotes,
            'quoteStatuses' => $quoteStatuses,
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
        $data = $this->lifeQuoteService->getFormOptions();

        return inertia('LifeQuote/Form', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(LifeQuoteRequest $request)
    {
        $response = $this->lifeQuoteService->storeLifeQuote($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('life-quotes-show', $response->quoteUID))->with('message', 'Quote is created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($uuid)
    {
        /* Start - Temporarily adding for correcting historic data  */
        $quote = $this->lifeQuoteService->getQuoteByColumn('uuid', $uuid);
        (new PaymentRepository)->updatePriceVatApplicableAndVat($quote, QuoteTypes::LIFE->value);
        /* End - Temporarily adding for correcting historic data  */

        $quoteShowData = $this->lifeQuoteService->getLifeQuoteShowData($quote);
        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments(QuoteTypes::LIFE->value, $quote->id);
        $bookPolicyDetails = $this->bookPolicyPayload($quote, QuoteTypes::LIFE->value, $quoteShowData['payments'], $quoteDocuments);
        $quoteShowData['bookPolicyDetails'] = $bookPolicyDetails;

        return inertia('LifeQuote/Show', $quoteShowData);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        $data = $this->lifeQuoteService->getFormOptions();
        $quote = $this->lifeQuoteService->getQuoteByColumn('uuid', $uuid);

        return inertia('LifeQuote/Form', array_merge($data, [
            'quote' => $quote,
        ]));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(LifeQuoteRequest $request, $uuid)
    {
        $this->lifeQuoteService->updateLifeQuote($uuid, $request->validated());

        return redirect(route('life-quotes-show', $uuid))->with('message', 'Quote is updated successfully.');
    }

    public function cardsView(Request $request)
    {
        $quotes = [
            ["id" => QuoteStatusEnum::Quoted, "text" => quoteStatusCode::QUOTED, "code" => quoteStatusCode::QUOTED, "data" => getDataAgainstStatus(QuoteTypes::LIFE->value, QuoteStatusEnum::Quoted, $request)],
            ["id" => QuoteStatusEnum::FollowedUp, "text" => quoteStatusCode::FOLLOWEDUP, "code" => quoteStatusCode::FOLLOWEDUP, "data" => getDataAgainstStatus(QuoteTypes::LIFE->value, QuoteStatusEnum::FollowedUp, $request)],
            ["id" => QuoteStatusEnum::ApplicationSubmitted, "text" => quoteStatusCode::APPLICATION_SUBMITTED, "code" => quoteStatusCode::APPLICATION_SUBMITTED, "data" => getDataAgainstStatus(QuoteTypes::LIFE->value, QuoteStatusEnum::ApplicationSubmitted, $request)],
            ["id" => QuoteStatusEnum::InNegotiation, "text" => quoteStatusCode::NEGOTIATION, "code" => quoteStatusCode::NEGOTIATION, "data" => getDataAgainstStatus(QuoteTypes::LIFE->value, QuoteStatusEnum::InNegotiation, $request)],
            ["id" => QuoteStatusEnum::PolicyBooked, "text" => quoteStatusCode::POLICY_BOOKED, "code" => quoteStatusCode::POLICY_BOOKED, "data" => getDataAgainstStatus(QuoteTypes::LIFE->value, QuoteStatusEnum::PolicyBooked, $request)],
        ];

        return inertia('LifeQuote/Cards', [
            'quotes' => array_values($quotes),
            'quoteTypeId' => QuoteTypes::LIFE->id(),
            'quoteType' => QuoteTypes::LIFE->value,

        ]);
    }
}
