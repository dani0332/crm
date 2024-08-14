<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookBulkPoliciesRequest;
use App\Repositories\QuoteTypeRepository;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;

class BulkPolicyBookingController extends Controller
{
    use GenericQueriesAllLobs;
    public function __construct()
    {
        $this->middleware('permission:'.PermissionsEnum::VIEW_BULK_POLICY_BOOKING_LIST, ['only' => ['index']]);
        $this->middleware('permission:'.PermissionsEnum::BOOK_BULK_POLICY_ON_SAGE, ['only' => ['sendBulkPoliciesForSageBooking']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $quotes = [];
        $quoteTypes = QuoteTypeRepository::all();
        if ($request->quoteType) {
            $quoteRepositoryObject = $this->getRepositoryObject($request->quoteType);
            $quotes = $quoteRepositoryObject::with(['advisor', 'payments', 'quoteStatus'])
                ->filter()
                ->withFakeLeadCriteria()
                ->orderBy('created_at', 'desc')
                ->simplePaginate(10)
                ->withQueryString();
        }

        return inertia('BulkPolicyBooking/Index', ['quotes' => $quotes, 'quoteTypes' => $quoteTypes]);
    }

    public function sendPoliciesForSageBulkBooking(BookBulkPoliciesRequest $request)
    {
        $quoteType = $request->model_type;
        $quoteIDs = $request->selectedQuoteIds;
        $quoteErrors = collect([]);
        foreach ($quoteIDs as $quoteID) {
            $quote = $this->getQuoteObject($quoteType, $quoteID);
            if ($quote) {
                $response = (new SageApiService())->postBookPolicyToSage($request, $quote);
                if (! $response['status']) {
                    /* Log error in globel table for this lead */;
                }

            }
        }
    }

}
