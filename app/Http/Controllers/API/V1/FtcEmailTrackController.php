<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FtcEmailTrackRequestStore;
use App\Services\FtcEmailTrackService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FtcEmailTrackController extends Controller
{
    use GenericQueriesAllLobs;

    protected $ftcEmailTrackService;

    public function __construct(FtcEmailTrackService $ftcEmailTrackService)
    {
        $this->ftcEmailTrackService = $ftcEmailTrackService;
    }

    public function store($quoteType, $quoteUuid, FtcEmailTrackRequestStore $request)
    {
        if ($quote = $this->getQuoteObject($quoteType, $quoteUuid)) {
            $payload = [
                'email' => $request->email,
                'subject' => $request->subject,
                'status' => $request->status,
                'link' => $request->link,
                'quote_trackable_id' => $quote->id,
                'quote_trackable_type' => get_class($quote),
            ];
            $ftcEmailTrack = $this->ftcEmailTrackService->createTrackEmail($payload);
            return response()->json(['message' => 'Email track created successfully.', 'data' => $ftcEmailTrack], Response::HTTP_CREATED);
        }

        return response()->json(['message' => 'Quote not found.'], 404);
    }

    
    public function update(Request $request)
    {
        $payload = [
            'status' => $request->status,
        ];
        $trackEmail = $this->ftcEmailTrackService->updateTrackEmail($payload, '', $request->link);
        return response()->json(['message' => 'Email track updated successfully.', 'data' => $trackEmail], Response::HTTP_OK);   
    }
}
