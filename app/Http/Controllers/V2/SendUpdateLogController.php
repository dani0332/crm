<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\PersonalQuote;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Services\LookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $quote = $this->getQuote($sendUpdateLog->personal_quote_id);

        if (in_array($quoteType, [QuoteTypes::CAR, QuoteTypes::HEALTH, QuoteTypes::TRAVEL])) {
            $quote->load('plan.insuranceProvider');
        }

        $issuanceStatuses = DB::table('policy_issuance_status')->select('id', 'text')->get();

        return inertia('SendUpdateLog/Show', [
            'quote' => $quote,
            'quoteType' => $quoteType,
            'sendUpdateLog' => $sendUpdateLog,
            'issuanceStatuses' => $issuanceStatuses,
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

        if (isset($log->message) && ! empty($log->message)) {
            vAbort($log->message);
        }

        $this->updateQuoteLeadStatus($data, 'update');

        return redirect()->back();
    }

    public function updateQuoteLeadStatus($data, $type)
    {
        $quoteUuid = $data['quote_uuid'];

        $quoteTypeId = $data['quote_type_id'];

        $selectedType = $data['childCategory']['slug'];

        $subType = $data['childCategory']['option'];

        $model = PersonalQuote::class;

        if ($type === 'create') {

            switch ($selectedType) {
                case SendUpdateLogStatusEnum::EF:
                    if ($subType && $subType['slug'] === 'MPC') {
                        $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::CancellationPending,
                        ]);
                    }
                    break;
                case SendUpdateLogStatusEnum::CI:
                case SendUpdateLogStatusEnum::CIR:
                    $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                        'quote_status_id' => QuoteStatusEnum::CancellationPending,
                    ]);
                    break;
            }
        } else {

            switch ($selectedType) {
                case SendUpdateLogStatusEnum::EF:
                case SendUpdateLogStatusEnum::CI:
                    if ($data['status'] === SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                        $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::PolicyCancelled,
                        ]);
                    }
                    break;
                case SendUpdateLogStatusEnum::CIR:
                    if ($data['status'] === SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                        $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::PolicyBooked,
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

    public function savePolicyDetails(Request $request)
    {
        $data = $request->all();

        SendUpdateLogRepository::savePolicyDetails($data);

        return redirect()->back();
    }

    private function getQuote($personalQuoteId)
    {
        $repository = 'App\\Repositories\\PersonalQuoteRepository';

        return $repository::where('id', $personalQuoteId)->first();
    }
}
