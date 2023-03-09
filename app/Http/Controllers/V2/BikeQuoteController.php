<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BikeQuoteRequest;
use App\Repositories\ActivityRepository;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;

class BikeQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $personalQuotes = BikeQuoteRepository::getData();

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::BIKE->id())->get();

        return inertia('BikeQuote/Index', [
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

        return back()->with('message', 'Quote created successfully');
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $data = BikeQuoteRepository::getFormOptions();

        $quote = BikeQuoteRepository::getBy('uuid', $uuid);

        return inertia('BikeQuote/Form', array_merge($data, [
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

        $quote->load('documents.createdBy');

        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::BIKE->id())->get();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();

        $insuranceProviders = InsuranceProviderRepository::getList();
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors();

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::BIKE->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        return inertia('BikeQuote/Show', [
            'quoteType' => QuoteTypes::BIKE,
            'quote' => $quote,
            'activities' => $activities,
            'lostReasons' => $lostReasons,
            'advisors' => $advisors,
            'quoteStatusesEnum' => QuoteStatusEnum::asArray(),
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
     * @param $quoteTypeCode
     * @param $quoteId
     * @return void
     */
    public function update($uuid, BikeQuoteRequest $request)
    {
        $response = BikeQuoteRepository::update($uuid, $request->validated());

        return back()->with('message', 'Quote updated successfully');
    }
}
