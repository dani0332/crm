<?php

namespace App\Http\Controllers\V2;

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\CarQuoteRequest;
use App\Http\Requests\ChangeInsurerRequest;
use App\Http\Requests\UpdateCarQuotePlanDetailsRequest;
use App\Jobs\NBEventFollowup;
use App\Models\QuoteBatches;
use App\Repositories\CarQuoteRepository;
use App\Repositories\UserRepository;
use App\Services\CarPlanService;
use App\Services\CarQuoteService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CarQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function getCarSoldQuotes()
    {
        $quotes = CarQuoteRepository::getLostQuotes(QuoteStatusEnum::CarSold);

        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::CAR->value);

        return inertia('LostQuotes/CarSold', [
            'quotes' => $quotes,
            'advisors' => $advisors,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function getCarUncontactableQuotes()
    {
        $quotes = CarQuoteRepository::getLostQuotes(QuoteStatusEnum::Uncontactable);

        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::CAR->value);

        return inertia('LostQuotes/CarUncontactable', [
            'quotes' => $quotes,
            'advisors' => $advisors,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index(Request $request)
    {
        $personalQuotes = [];

        if ($request->page) {
            $personalQuotes = CarQuoteRepository::getData()->withQueryString();
        }

        $advisors = CarQuoteRepository::getAdvisors();
        $quoteBatches = QuoteBatches::get();

        return inertia('CarQuote/Index', [
            'quotes' => $personalQuotes,
            'advisors' => $advisors,
            'quoteBatches' => $quoteBatches,
            'kyoEndPoint' => env('KYO_END_POINT'),
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $data = CarQuoteRepository::getFormOptions();

        return inertia('CarQuote/Form', $data);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(CarQuoteRequest $request)
    {
        $response = CarQuoteRepository::create($request->validated());

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
        $data = CarQuoteRepository::getFormOptions();

        $quote = CarQuoteRepository::getBy('uuid', $uuid);

        return inertia(
            'CarQuote/Form',
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
        return inertia('CarQuote/Show', []);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($uuid, CarQuoteRequest $request)
    {
        CarQuoteRepository::update($uuid, $request->validated());

        return back();
    }

    /**
     * @param  Request  $requestvabovabovabovabo
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeInsurer(ChangeInsurerRequest $request)
    {
        $response = CarQuoteRepository::changeInsurer($request->validated());

        return response()->json($response);
    }

    public function updateCarPlanDetails(UpdateCarQuotePlanDetailsRequest $request)
    {
        $response = CarQuoteRepository::updateCareQuotePlanDetails($request->validated());

        return redirect()->back(); // response()->json($response);
    }

    public function search(CarQuoteRequest $request)
    {
        $personalQuotes = [];

        if ($request->ajax) {
            $personalQuotes = CarQuoteRepository::getData();
        }

        return inertia('CarQuote/Index', [
            'quotes' => $personalQuotes,
        ]);
    }

    public function carPlanUpdateManualProcess(Request $request)
    {
        $response = app(CarQuoteService::class)->carPlanModify($request);

        $message = 'Car Plan has not been updated';

        if (gettype($response) == GenericRequestEnum::INTEGER && ($response == 200 || $response == 201)) {
            $message = 'Plan has been updated';

            return redirect()->back()->with('message', $message);
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }
            $message = 'Car Plan has not been updated '.$responseMessage;
        }

        return redirect()->back()->with('error', $message);
    }

    public function carPlansByInsuranceProvider(Request $request)
    {
        $insuranceProviderId = $request->insuranceProviderId;
        $quoteUuId = $request->quoteUuId;

        $quotePlans = app(CarQuoteService::class)->getQuotePlans($quoteUuId);

        $quotePlanId = [];
        $listQuotePlans = [];
        if (isset($quotePlans->quotes->plans)) {
            $listQuotePlans = $quotePlans->quotes->plans;
        }

        foreach ($listQuotePlans as $key => $quotePlan) {
            if (! isset($quotePlan->id)) {
                continue;
            }

            $quotePlanId[] = $quotePlan->id;
        }

        $carPlans = app(CarPlanService::class)->getNonQuotedCarPlans($insuranceProviderId, $quotePlanId);

        return response()->json($carPlans);
    }

    public function sendNBEventFollowup(Request $request)
    {

        if (count($request->uuids) < 1) {
            return back()->with('error', 'No UUID provided');
        }

        foreach ($request->uuids as $uuid) {
            NBEventFollowup::dispatch($uuid, $request->followup_type)->delay(Carbon::now()->addMinutes(2));
        }

        return back()->with('success', 'Event Followup sending successful');
    }
}
