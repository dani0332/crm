<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FollowupStartedRequest;
use App\Http\Requests\Api\UpdateLeadStatusRequest;
use App\Models\CarQuote;
use App\Models\QuoteStatus;
use App\Repositories\CarQuoteRepository;
use App\Services\CarQuoteService;
use App\Services\QuoteStatusService;
use Illuminate\Http\Request;
use App\Traits\GenericQueriesAllLobs;

class CarQuoteController extends Controller
{
    use GenericQueriesAllLobs;
    /**
     * @return void
     */
    public function index()
    {
        $quotes = CarQuoteRepository::select(
            ['id', 'code', 'uuid', 'advisor_id', 'renewal_batch', 'quote_batch_id']
        )->filter()
            ->simplePaginate();

        return response()->json($quotes);
    }

    public function getFollowupLeads()
    {
        $quotes = CarQuoteRepository::select(['id', 'code', 'uuid', 'advisor_id', 'renewal_batch', 'quote_batch_id'])
            ->whereHas('carQuoteRequestDetail', function ($q) {
                $q->whereNotNull('ocb_sent_date');
            })
            ->with(['carQuoteRequestDetail' => function ($q) {
                $q->whereNotNull('ocb_sent_date');
            }])->whereNotIn('source', [LeadSourceEnum::REVIVAL, LeadSourceEnum::REVIVAL_REPLIED, LeadSourceEnum::REVIVAL_PAID])
            ->where('quote_status_id', '<>', QuoteStatusEnum::Duplicate)
            ->filter()
            ->simplePaginate((request()->limit ?? 100));

        return response()->json($quotes);
    }

    public function show($uuid)
    {
        $quote = CarQuote::where('uuid', $uuid)
            ->with('advisor')
            ->first();

        return response()->json($quote);
    }

    /**
     * get ocb details
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOcbDetails($uuid, CarQuoteService $carQuoteService)
    {
        $ocbDetails = $carQuoteService->getOcbDetails($uuid);

        return response()->json($ocbDetails);
    }

    /**
     * @return void
     */
    public function updateQuoteStatus(UpdateLeadStatusRequest $request)
    {
        $quoteStatus = QuoteStatus::find($request->quote_status_id)->code ?? null;
        $quoteTypeId = QuoteTypes::getIdFromValue($request->quote_type);
        app(QuoteStatusService::class)->updateQuoteStatus($quoteTypeId, $request->quote_uuid, $quoteStatus, [], $request->notes);

        return response()->json(['success' => true, 'message' => 'Lead status updated successfully']);
    }

    /**
     * this will be called when 1st followup email will be sent
     * upon this need to update quote status to followed-up
     * and set kyo followup id in detail table
     *
     * @return void
     */
    public function followupStarted(FollowupStartedRequest $request)
    {
        CarQuoteRepository::followupStarted($request->validated());

        return response()->json(['success' => true]);
    }

    public function updatePauseAndResumeCounters(Request $request)
    {

        $validatedData = $request->validate([
            'quote_uuid' => 'required|string',
            'action' => 'required|string|in:pause,resume',
        ]);

        return app(CarQuoteService::class)->pauseAndResumeFollowUpCounters($validatedData);
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

        // Generate PDF using the service
        $result = app(CarQuoteService::class)->exportCompanyCarPdf($quoteType, $data, $quotePlans, $imageData);

        if (isset($result['error'])) {
            return response()->json($result);
        }

        $pdf = $result['pdf'];
        $pdfName = $result['name'];

        // For JSON response
        $pdfContent = $pdf->output();

        return response()->json(['data' => 'data:application/pdf;base64,'.base64_encode($pdfContent), 'name' => $pdfName]);
    }
}
