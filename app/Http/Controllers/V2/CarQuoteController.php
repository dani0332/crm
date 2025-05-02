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
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CarQuoteController extends Controller
{
    use GenericQueriesAllLobs;

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

    public function generateCompanyCarPdf(Request $request)
    {
        $data = [
            'quote_uuid' => $request->quote_uuid,
            'plan_ids' => $request->plan_ids,
            'addons' => null,
        ];

        $planIds = $data['plan_ids'];
        $addons = $data['addons'] ?? null;

        $quotePlans = app(CarQuoteService::class)->getQuotePlans($data['quote_uuid']);

        if (! isset($quotePlans->quotes->plans)) {
            return ['error' => 'Quote plans not available'];
        }

        $quoteType = 'car';
        $quote = $this->getQuoteObjectBy($quoteType, $data['quote_uuid'], 'uuid');

        $quote->load(['carMake', 'carModel', 'advisor' => function ($q) {
            $q->select('id', 'email', 'mobile_no', 'name', 'landline_no', 'profile_photo_path');
        }, 'customer', 'vehicleType']);

        // Configure DomPDF options
        $options = new \Dompdf\Options;
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'Prompt');
        $options->set('defaultMediaType', 'print');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultPaperSize', 'A4');
        $options->set('defaultPaperOrientation', 'portrait');
        $options->set('chroot', public_path()); // Set root directory for image access

        // Disable debug options to prevent debug output
        $options->set('debugKeepTemp', false);
        $options->set('debugCss', false);
        $options->set('debugLayout', false);

        // Enable CSS floating to ensure proper layout
        $options->set('isJavascriptEnabled', true);
        $options->set('fontCache', storage_path('fonts'));
        $options->set('tempDir', storage_path('app/dompdf'));

        // Additional rendering settings
        $options->set('enable_css_float', true);
        $options->set('enable_html5_parser', true);
        $options->set('enable_font_subsetting', true);
        $options->set('dpi', 150);
        $options->set('enable_remote', true);

        // Create DomPDF instance with options
        $dompdf = new \Dompdf\Dompdf($options);

        // Convert public_path image references to base64 for embedding
        $imagePaths = [
            'car-banner-pdf-1.png',
            'car-pdf-banner-2.png',
            'home_pdf_second_last_page_with_header.jpg',
            'car-comparision-4-image-1.png',
            'quote_plans_pages/ecom_home/open_in_new_icon.png',
            'quote_plans_pages/ecom_home/mail_icon.png',
            'quote_plans_pages/ecom_home/smartphone_icon.png',
            'whatsapp-small.png',
            'quote_plans_pages/ecom_home/phone_callback_icon.png',
            'quote_plans_pages/ecom_home/call_icon.png',
        ];

        $imageData = [];
        foreach ($imagePaths as $path) {
            $fullPath = public_path('images/'.$path);
            if (file_exists($fullPath)) {
                $type = pathinfo($fullPath, PATHINFO_EXTENSION);
                $imageData[$path] = 'data:image/'.$type.';base64,'.base64_encode(file_get_contents($fullPath));
            }
        }

        // Generate PDF view content using the DOM template (which includes header and footer)
        $html = view('pdf.car_comparision.commercial_car_pdf', compact('quotePlans', 'planIds', 'quote', 'addons', 'imageData'))->render();

        // Load the HTML into DomPDF
        $dompdf->loadHtml($html);

        // Render PDF
        $dompdf->render();

        $pdfName = 'InsuranceMarket.ae™ Motor Insurance Comparison for '.$quote->first_name.' '.$quote->last_name.'.pdf';

        // For JSON response (alternative approach)
        $pdfContent = $dompdf->output();

        return response()->json(['data' => 'data:application/pdf;base64,'.base64_encode($pdfContent), 'name' => $pdfName]);
    }

}
