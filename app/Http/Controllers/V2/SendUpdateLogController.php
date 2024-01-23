<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlanDetailsRequest;
use App\Models\IndicativeAdditionalPrice;
use App\Models\PersonalQuote;
use App\Repositories\IndicativeAdditionalPriceRepository;
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

        $quoteTypeId = $sendUpdateLog->quote_type_id;

        $sendUpdateOptions = (new LookupService)->getSendUpdateOptions($quoteTypeId);
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping($quoteTypeId);

        $quoteType = QuoteTypes::getName($quoteTypeId)->value;

        $quote = $this->getQuote($sendUpdateLog->personal_quote_id, $quoteType);

        return inertia('SendUpdateLog/Show', [
            'quote' => $quote,
            'quoteType' => $quoteType,
            'sendUpdateLog' => $sendUpdateLog,
            'sendUpdateOptions' => $sendUpdateOptions,
            'insuranceProviders' => $insuranceProviders,
            'sendUpdateStatusEnum' => SendUpdateLogStatusEnum::asArray(),
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

    public function updateQuoteLeadStatus($data, $type)
    {
        $quoteUuid = $data['reportable_uuid'];

        $quoteTypeId = $data['quote_type_id'];
        
        $selectedType = $data['childCategory']['slug'];
        
        $subType = $data['childCategory']['option'];

        $model = PersonalQuote::class;
        
        if ($type === 'create') {

            switch ($selectedType) {
                case 'EF':
                    if ($subType && $subType['slug'] === 'MPC') {
                        $model::where(['id' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::CancellationPending
                        ]);
                    }
                    break;
                case 'CI':
                case 'CIR':
                    $model::where(['id' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                        'quote_status_id' => QuoteStatusEnum::CancellationPending
                    ]);
                    break;
            }
        } else {
            
            switch ($selectedType) {
                case 'EF':
                case 'CI':
                    if ($data['status'] === SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                        $model::where(['id' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::PolicyCancelled
                        ]);
                    }
                    break;
                case 'CIR':
                    if ($data['status'] === SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                        $model::where(['id' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::PolicyBooked
                        ]);

                        // TODO: send it to sage, need to confirm what the sage is.
                    }
                    break;
            }
        }
    }

    public function savePriceDetails(Request $request)
    {
        $data = $request->all();

        SendUpdateLogRepository::updateLogPriceDetails($data);
        
        return redirect()->back();
    }

    private function getQuote($quoteId, $quoteType)
    {
        $repository = getRepositoryObject($quoteType);

        return $repository::where('id', $quoteId)->first();
    }
}
