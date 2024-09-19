<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookBulkPoliciesRequest;
use App\Jobs\BulkPolicyBookingOnSage;
use App\Models\SageProcess;
use App\Repositories\QuoteTypeRepository;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;

class SageProcessController extends Controller
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
            $quoteModelObject = $this->getModelObject($request->quoteType);
            $quotes = SageProcess::with(['model', 'model.advisor', 'model.quoteStatus'])
                ->where('model_type', app($quoteModelObject)::class)
                ->orderBy('created_at', 'asc')
                ->simplePaginate(10)
                ->withQueryString();
        }

        return inertia('SageProcess/Index', ['quotes' => $quotes, 'quoteTypes' => $quoteTypes]);
    }

    public function sendPoliciesForSageBulkBooking(BookBulkPoliciesRequest $request)
    {

        BulkPolicyBookingOnSage::dispatch($request)->onQueue('sage-processes');

        return ['status' => true, 'message' => 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!'];

    }

}
