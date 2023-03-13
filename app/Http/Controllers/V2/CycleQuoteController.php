<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BikeQuoteRequest;
use App\Http\Requests\CycleQuoteRequest;
use App\Repositories\ActivityRepository;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\CycleQuoteRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;

class CycleQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $personalQuotes = CycleQuoteRepository::getData();

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::CYCLE->id())->get();

        return inertia('CycleQuote/Index', [
            'quotes' => $personalQuotes,
            'quoteStatuses' => $quoteStatuses,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $data = BikeQuoteRepository::getFormOptions();

        return inertia('CycleQuote/Form', $data);
    }

    /**
     * @param $quoteTypeCode
     * @param  BikeQuoteRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CycleQuoteRequest $request)
    {
        $response = CycleQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return back()->with('message', 'Quote created successfully');
    }

    /**
     * @param $uuid
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $data = CycleQuoteRepository::getFormOptions();

        $quote = CycleQuoteRepository::getBy('uuid', $uuid);

        return inertia('CycleQuote/Form', array_merge($data, [
            'quote' => $quote,
        ])
        );
    }

    /**
     * @param $quoteTypeCode
     * @param $quoteId
     * @param  BikeQuoteRequest  $request
     * @return void
     */
    public function update($uuid, CycleQuoteRequest $request)
    {
        CycleQuoteRepository::update($uuid, $request->validated());

        return back()->with('message', 'Quote updated successfully');
    }

    /**
     * @param $uuid
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {
        $quote = CycleQuoteRepository::getBy('uuid', $uuid);

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::CYCLE->id())->get();

        $quote->load('documents.createdBy');

        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::CYCLE->id())->get();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();

        $insuranceProviders = InsuranceProviderRepository::getList();
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors();

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::CYCLE->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        return inertia('CycleQuote/Show', [
            'quoteType' => QuoteTypes::CYCLE,
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
            'can' => [
                'approve_payments' => auth()->user()->can(PermissionsEnum::ApprovePayments),
                'edit_payments' => auth()->user()->can(PermissionsEnum::PaymentsEdit),
                'create_payments' => auth()->user()->can(PermissionsEnum::PaymentsCreate) && ! auth()->user()->hasRole(RolesEnum::PA),
                'isPA' => auth()->user()->hasRole(RolesEnum::PA),
            ],
        ]);
    }
}
