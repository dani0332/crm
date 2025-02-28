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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
