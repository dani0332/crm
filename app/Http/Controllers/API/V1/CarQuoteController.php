<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FollowupStartedRequest;
use App\Http\Requests\Api\UpdateLeadStatusRequest;
use App\Models\CarQuote;
use App\Repositories\CarQuoteRepository;
use App\Services\CarQuoteService;
use App\Services\QuoteStatusService;
use Illuminate\Http\Request;

class CarQuoteController extends Controller
{
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
        $quoteStatus = QuoteStatusEnum::getKey($request->quote_status_id);
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
}
