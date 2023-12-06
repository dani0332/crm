<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Repositories\SendUpdateLogRepository;
use App\Services\LookupService;
use Illuminate\Http\Request;

class SendUpdateLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        $response = SendUpdateLogRepository::create($request->all());

        if (! empty($response->message)) {
            vAbort($response->message);
        }

        return redirect(route('quotes.car.view-update-log', ['id' => $request->reportable_uuid, 'uuid' => $response->uuid]));
    }

    /**
     * Display the specified resource.
     */
    public function show($id, $uuid)
    {
        $sendUpdateLog = SendUpdateLogRepository::getLogByUuid($uuid);

//        $quoteTyeId = $this->getQuoteTypeId($sendUpdateLog->reportable_type);

        $sendUpdateOptions = (new LookupService)->getSendUpdateOptions($id);

        return inertia('SendUpdateLog/Show', [
            'quoteId' => $id,
            'sendUpdateLog' => $sendUpdateLog,
            'sendUpdateOptions' => $sendUpdateOptions,
        ]);
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

    private function getQuoteTypeId()
    {
        //
    }
}
