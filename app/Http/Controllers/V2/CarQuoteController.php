<?php

namespace App\Http\Controllers\V2;

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\CarQuoteRequest;
use App\Http\Requests\ChangeInsurerRequest;
use App\Http\Requests\UpdateCarOCRWebFormDataRequest;
use App\Http\Requests\UpdateCarQuotePlanDetailsRequest;
use App\Jobs\NBEventFollowup;
use App\Models\QuoteBatches;
use App\Repositories\CarQuoteRepository;
use App\Repositories\UserRepository;
use App\Services\CarPlanService;
use App\Services\CarQuoteService;
use App\Services\CustomerVerification\CustomerVerificationService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Response;
use Inertia\ResponseFactory;

class CarQuoteController extends Controller
{
    use GenericQueriesAllLobs;

    public function __construct(
        private CustomerVerificationService $customerVerificationService
    ) {}

    /**
     * @return Response|ResponseFactory
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
     * @return Response|ResponseFactory
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
     * @return Response|ResponseFactory
     */
    public function index(Request $request)
    {
        $personalQuotes = [];

        if ($request->page) {
            $personalQuotes = CarQuoteRepository::getData()->withQueryString();
        }

        $advisors = Cache::remember('car_quote_advusors', now()->addMinutes(5), fn () => CarQuoteRepository::getAdvisors());
        $quoteBatches = Cache::remember('quote_batches', now()->addHour(), fn () => QuoteBatches::get());

        return inertia('CarQuote/Index', [
            'quotes' => $personalQuotes,
            'advisors' => $advisors,
            'quoteBatches' => $quoteBatches,
            'kyoEndPoint' => env('KYO_END_POINT'),
        ]);
    }

    /**
     * @return Response|ResponseFactory
     */
    public function create()
    {
        $data = CarQuoteRepository::getFormOptions();

        return inertia('CarQuote/Form', $data);
    }

    /**
     * @return RedirectResponse
     *
     * @throws ValidationException
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
     * @return Response|ResponseFactory
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
     * @return Response|ResponseFactory
     */
    public function show($uuid)
    {
        return inertia('CarQuote/Show', []);
    }

    /**
     * @return RedirectResponse
     */
    public function update($uuid, CarQuoteRequest $request)
    {
        CarQuoteRepository::update($uuid, $request->validated());

        return back();
    }

    /**
     * @param  Request  $requestvabovabovabovabo
     * @return JsonResponse
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

            if ($request->expectsJson()) {
                return $message;
            }

            return redirect()->back()->with('message', $message);
        }

        if (isset($response->message)) {
            $responseMessage = $response->message;
        } else {
            $responseMessage = $response;
        }
        $message = 'Car Plan has not been updated '.$responseMessage;

        return $request->expectsJson() ? $message : redirect()->back()->with('message', $message);
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

    public function updateOcrWebformData(UpdateCarOCRWebFormDataRequest $request, int $quoteId)
    {
        $this->customerVerificationService->updateCarOcrWebformData($quoteId);

        return response()->json([
            'message' => 'OCR webform data updated successfully',
        ]);
    }
}
