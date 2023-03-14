<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\JetskiQuoteRequest;
use App\Repositories\ActivityRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\JetskiQuoteRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;

class JetskiQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $quotes = JetskiQuoteRepository::getData();

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::JETSKI->id())->get();

        return inertia('JetskiQuote/Index', [
            'quotes' => $quotes,
            'quoteStatuses' => $quoteStatuses,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $data = JetskiQuoteRepository::getFormOptions();

        return inertia('JetskiQuote/Form', $data);
    }

    /**
     * @param  JetskiQuoteRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(JetskiQuoteRequest $request)
    {
        $response = JetskiQuoteRepository::create($request->validated());

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
        $data = JetskiQuoteRepository::getFormOptions();

        $quote = JetskiQuoteRepository::getBy('uuid', $uuid);

        return inertia('JetskiQuote/Form', array_merge($data, [
            'quote' => $quote,
        ])
        );
    }

    /**
     * @param $uuid
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {
        $quote = JetskiQuoteRepository::getBy('uuid', $uuid);

        $quote->payments->each->setAppends(['allow', 'copy_link_button', 'edit_button', 'approve_button', 'approved_button']);

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::JETSKI->id())->get();

        $quote->load('documents.createdBy');

        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::JETSKI->id())->get();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();

        $insuranceProviders = InsuranceProviderRepository::getList();
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors();

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::JETSKI->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        return inertia('JetskiQuote/Show', [
            'quoteType' => QuoteTypes::JETSKI,
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

    /**
     * @param $uuid
     * @param  JetskiQuoteRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($uuid, JetskiQuoteRequest $request)
    {
        JetskiQuoteRepository::update($uuid, $request->validated());

        return back();
    }
}
