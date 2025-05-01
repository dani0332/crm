<?php

namespace App\Http\Controllers;

use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;

class FtcEmailLogController extends Controller
{
    use GenericQueriesAllLobs;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $quoteType = $request->quoteTrackableType;
        $quoteObject = $this->getQuoteObject($quoteType, $request->quoteTrackableId);
        $emailLogs = $quoteObject->ftcEmailLogs()->get();

        return response()->json($emailLogs);
    }
}
