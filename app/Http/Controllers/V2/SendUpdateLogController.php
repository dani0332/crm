<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlanDetailsRequest;
use App\Models\IndicativeAdditionalPrice;
use App\Repositories\InsuranceProviderRepository;
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
        $data = $request->all();
        $response = SendUpdateLogRepository::create($data);

        if (! empty($response->message)) {
            vAbort($response->message);
        }

        $this->updateQuoteLeadStatus($data, 'create');

        return redirect(route('send-update-logs.show', ['uuid' => $response->uuid, 'refURL' => $data['refURL']]));
    }

    /**
     * Display the specified resource.
     */
    public function show($uuid)
    {
        $sendUpdateLog = SendUpdateLogRepository::getLogByUuid($uuid);

        $sendUpdateOptions = (new LookupService)->getSendUpdateOptions($sendUpdateLog->quote_type_id);
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping($sendUpdateLog->quote_type_id);

        return inertia('SendUpdateLog/Show', [
            'sendUpdateLog' => $sendUpdateLog,
            'sendUpdateOptions' => $sendUpdateOptions,
            'insuranceProviders' => $insuranceProviders,
            'sendUpdateStatusEnum' => SendUpdateLogStatusEnum::asArray(),
            'quoteType' => QuoteTypes::getName($sendUpdateLog->quote_type_id)
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
        $data = $request->all();

        $log = SendUpdateLogRepository::updateLog($id, $data);

        if (isset($log->message) && !empty($log->message)) {
            vAbort($log->message);
        }

        $this->updateQuoteLeadStatus($data, 'update');

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    // public function getLogsById($id)
    // {
    //     $logs = SendUpdateLogRepository::getLogsById($id);

    //     return response()->json(compact('logs'));
    // }

    public function updateQuoteLeadStatus($data, $type)
    {
        $quoteId = $data['reportable_id'];
        
        $selectedType = $data['childCategory']['slug'];
        
        $subType = $this->getSubType($data['childCategory']['childs'], $data['option']);

        $model = $data['reportable_type'];
        
        if ($type === 'create') {

            switch ($selectedType) {
                case 'EF':
                    if ($subType['slug'] === 'MPC') {
                        $model::where('id', $quoteId)->update([
                            'quote_status_id' => QuoteStatusEnum::CancellationPending
                        ]);
                    }
                    break;
                case 'CI':
                case 'CIR':
                    $model::where('id', $quoteId)->update([
                        'quote_status_id' => QuoteStatusEnum::CancellationPending
                    ]);
                    break;
            }
        } else {
            
            switch ($selectedType) {
                case 'EF':
                case 'CI':
                    if ($data['status'] === SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                        $model::where('id', $quoteId)->update([
                            'quote_status_id' => QuoteStatusEnum::PolicyCancelled
                        ]);
                    }
                    break;
                case 'CIR':
                    if ($data['status'] === SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                        $model::where('id', $quoteId)->update([
                            'quote_status_id' => QuoteStatusEnum::PolicyBooked
                        ]);

                        // TODO: send it to sage, need to confirm what the sage is.
                    }
                    break;
            }
        }
    }

    private function getSubType($list, $optionId) 
    {
        return collect($list)->where('id', $optionId)->first();
    }

    public function saveIndicativePrices(Request $request)
    {
        $data = $request->all();

        $response = IndicativeAdditionalPrice::firstOrCreate([
            'send_update_log_id' => $data['send_update_log_id'],
        ], $data);

        $sendUpdateLog = SendUpdateLogRepository::getLogByUuid($data['uuid']);

        $sendUpdateLog->update(['status' => SendUpdateLogStatusEnum::REQUEST_IN_PROGRESS]);

        return redirect()->back()->with('success', 'updated successfully');
    }

    public function savePlanDetails(PlanDetailsRequest $request)
    {
        $id = $request->id;
        $quoteTypeId = $request->quote_type_id;
        
        $quoteType = QuoteTypes::getName($quoteTypeId);

        $repository = getRepositoryObject($quoteType);

        $quote = $repository::where('id', $id)->firstOrFail();
        $quote->update($request->validated());

        return redirect()->back()->with('success', 'updated successfully');
    }
}
