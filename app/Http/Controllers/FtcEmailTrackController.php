<?php

namespace App\Http\Controllers;

use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;

class FtcEmailTrackController extends Controller
{
    use GenericQueriesAllLobs;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $quoteType = $request->quoteTrackableType;
        $quoteObject = $this->getQuoteObject($quoteType, $request->quoteTrackableId);
        $emailTracks = $quoteObject->ftcEmailTracks()->get();

        return response()->json($emailTracks);
    }
}
