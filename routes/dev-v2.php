<?php

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Jobs\CQF\ProcessNonMotorCQFOrchestratorJob;
use App\Jobs\SendUpdateToCustomerJob;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Support\Facades\Route;

Route::get('dev-route', function () {
    return response()->json([
        'message' => 'Dev route',
    ]);
});

/**
 * Policy Issuance Routes
 */
Route::group(['prefix' => 'policy-issuance'], function () {

    /**
     * Test send book policy documents job
     */
    Route::get('/test-send-book-policy-documents-job/{quoteId}', function ($quoteId) {
        $quoteId = $quoteId ?? 306194;
        $quoteObject = PersonalQuote::find($quoteId);
        $quoteType = QuoteTypes::CYBER->value;
        $quoteObject->advisor_id = null;
        $quoteObject->quote_status_id = QuoteStatusEnum::PolicyBooked;
        $quoteObject->save();

        $data = new stdClass;
        $data->model_type = strtolower($quoteType);
        $data->quote_id = $quoteObject->id;

        $policyIssuanceService = app(PolicyIssuanceService::class);
        $policyIssuanceService->allocateLead($quoteType, $quoteObject, false, '', '');
    });

    /**
     * Test send update to customer job
     */
    Route::get('/test-send-update-to-customer-job/{sendUpdateLogId}', function ($sendUpdateLogId) {
        $sendUpdateLog = SendUpdateLog::find($sendUpdateLogId);
        $payload = new stdClass;
        $payload->sendUpdateId = $sendUpdateLogId;
        $payload->quoteType = QuoteTypes::CYBER->value;
        $payload->action = SendUpdateLogStatusEnum::ACTION_SNBU;
        $payload->quoteUuid = $sendUpdateLog->quote_uuid;
        $payload->quoteRefId = $sendUpdateLog->quote_ref_id;
        $payload->paymentValidated = true;
        $payload->inslyMigrated = 0;
        $payload->isEmailSent = 0;
        $payload->reversalInvoice = '';
        $payload->quoteCode = $sendUpdateLog->quote_code;
        $payload->code = $sendUpdateLog->code;

        SendUpdateToCustomerJob::dispatch($sendUpdateLogId, $payload);

        return response()->json([
            'message' => 'Send update to customer job dispatched',
        ]);
    });
});

/**
 * Test non motor CQF renewal orchestrator job
 */
Route::middleware(['auth'])->group(function () {
    Route::get('/test-non-motor-cqf-renewal-orchestrator-job', function () {
        ProcessNonMotorCQFOrchestratorJob::dispatch();

        return response()->json([
            'message' => 'Non motor CQF renewal orchestrator job dispatched',
        ]);
    });
});
