<?php

namespace App\Http\Controllers\V2;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\SendUpdateLogService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SendUpdateLogController extends Controller
{
    private object $sendUpdateLogService;

    use GenericQueriesAllLobs;

    public function __construct()
    {
        $this->sendUpdateLogService = app(SendUpdateLogService::class);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $childLeadResponse = [];
            $requestData = $request->all();
            $categoryCode = $requestData['childCategory']['slug'];

            $response = SendUpdateLogRepository::create($requestData);
            abort_if(! empty($response->message), 400, $response->message);

            $this->updateQuoteLeadStatus($requestData, 'create');
            if ($categoryCode == SendUpdateLogStatusEnum::CIR) {
                $quoteType = QuoteType::where('id', $requestData['quote_type_id'])->first();
                $quoteModel = $this->getModelObject($quoteType->code);
                $childLeadResponse = $this->sendUpdateLogService->createChildLead($quoteModel, $requestData, $quoteType->code);
            }

            DB::commit();

        } catch (\Exception $exception) {
            DB::rollBack();
            info('Create send update - Failed - Error : '.$exception->getMessage());

            return redirect()->back()->with('error', 'Failed to create send update');
        }

        if (! empty($childLeadResponse)) {
            if ($childLeadResponse['childLeadsCount'] == 0) {
                if (checkPersonalQuotes($childLeadResponse['quote_type_code'])) {
                    return redirect('/personal-quotes/'.strtolower($quoteType->code).'/'.$childLeadResponse['uuid'])
                        ->with('success', $childLeadResponse['ref_id'].' has been created');
                } else {
                    return redirect('/quotes/'.strtolower($quoteType->code).'/'.$childLeadResponse['uuid'])
                        ->with('success', $childLeadResponse['ref_id'].' has been created');
                }

            } else {
                return redirect()->back()->with('error', $childLeadResponse['parent_ref_id'].'-'.$childLeadResponse['childLeadsCount'].' is already created');
            }
        }

        return redirect(route('send-update-logs.show', [
            'uuid' => $response->uuid,
            'quoteUuid' => $requestData['quote_uuid'],
            'refURL' => $requestData['refURL'],
        ]));
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
        $quoteDocuments = app(QuoteDocumentService::class)->getQuoteDocumentsForSendUpdates($sendUpdateLog->id);
        $categoryCode = LookupRepository::where('id', $sendUpdateLog->category_id)->value('code');
        $isBookingDetailsVisible = $this->isBookingDetailsVisible($categoryCode, $quoteDocuments);

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
            'isBookingDetailsVisible' => $isBookingDetailsVisible,
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
                            'quote_status_id' => QuoteStatusEnum::PolicyCancelled,
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

    public function isBookingDetailsVisible($categoryCode, $quoteDocuments): bool
    {
        $_return = false;
        $documentTypes = $quoteDocuments->pluck('document_type_code')->toArray();

        if (count(array_intersect($documentTypes, [DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE])) > 0) {
            $_return = true;
            $categories = [
                SendUpdateLogStatusEnum::EF,
                SendUpdateLogStatusEnum::CI,
                SendUpdateLogStatusEnum::CIR,
                SendUpdateLogStatusEnum::CPU,
            ];

            if (in_array($categoryCode, $categories)) {
                $requiredDocumentTypes = [DocumentTypeCode::SEND_UPDATE_TAX_INVOICE, DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER];
                $_return = count(array_intersect($documentTypes, $requiredDocumentTypes)) == count($requiredDocumentTypes);
            }
        }

        return $_return;
    }
}
