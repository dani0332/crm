<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BikeQuoteRequest;
use App\Http\Requests\YachtQuoteRequest;
use App\Repositories\ActivityRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
use App\Repositories\YachtQuoteRepository;

class YachtQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $personalQuotes = YachtQuoteRepository::getData();

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::YACHT->id())->get();

        return inertia('YachtQuote/Index', [
            'quotes' => $personalQuotes,
            'quoteStatuses' => $quoteStatuses,
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

        $quote->load('documents.createdBy');

        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::YACHT->id())->get();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();

        $insuranceProviders = InsuranceProviderRepository::getList();
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::YACHT->value);

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::YACHT->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::YACHT->id(), $quote->id);

        return inertia('YachtQuote/Show', [
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
            'embeddedProducts' => $embeddedProducts,
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
