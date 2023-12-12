<?php

namespace App\Http\Controllers\V2;

use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\Controller;
use App\Repositories\SendUpdateLogRepository;
use App\Services\LookupService;
use Illuminate\Http\Request;

class SendUpdateLogController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $response = SendUpdateLogRepository::create($request->all());

        if (! empty($response->message)) {
            vAbort($response->message);
        }

        return redirect(route('quotes.car.view-update-log', ['id' => $request->reportable_uuid, 'code' => $response->code]));
    }

    /**
     * Display the specified resource.
     */
    public function show($id, $code)
    {
        $sendUpdateLog = SendUpdateLogRepository::getLogByCode($code);

        $sendUpdateOptions = (new LookupService)->getSendUpdateOptions($sendUpdateLog->quote_type_id);

        return inertia('SendUpdateLog/Show', [
            'quoteId' => $id,
            'sendUpdateLog' => $sendUpdateLog,
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateStatusEnum' => SendUpdateLogStatusEnum::asArray()
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
        $log = SendUpdateLogRepository::updateLog($id, $request->all());

        if (isset($log->message) && !empty($log->message)) {
            vAbort($log->message);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getLogsById($id)
    {
        $logs = SendUpdateLogRepository::getLogsById($id);

        return response()->json(compact('logs'));
    }
}
