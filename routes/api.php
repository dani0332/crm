<?php

use App\Http\Controllers\API\ActivityController;
use App\Http\Controllers\API\ApiController;
use App\Http\Controllers\API\EpCancellationCallbackController;
use App\Http\Controllers\API\HealthPlanController;
use App\Http\Controllers\API\HealthRateControlController;
use App\Http\Controllers\API\HealthRateController;
use App\Http\Controllers\API\V1\BorController;
use App\Http\Controllers\API\V1\CarQuoteController;
use App\Http\Controllers\API\V1\EmbeddedProductController;
use App\Http\Controllers\API\V1\FtcEmailLogController;
use App\Http\Controllers\API\V1\GenericLobController;
use App\Http\Controllers\API\V1\LifeController;
use App\Http\Controllers\API\V1\QuoteDocumentController;
use App\Http\Controllers\FtcEmailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\V2\AlfredChatController;
use App\Http\Controllers\V2\AMLController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['basicAuth'])->group(function () {
    Route::post('/alfred/signupLink', [ApiController::class, 'fetchSignupUrl']);
    Route::post('/imcrm/re-trigger-life-revival', [ApiController::class, 'reTriggerLifeRevival'])->name('reTriggerLifeRevival');
});

Route::prefix('v1')->middleware(['basicAuth'])->group(function () {
    Route::post('/instant-alfred/generate-export-url', [AlfredChatController::class, 'generateExportUrl'])->name('api.instant-alfred.generate-url');
    Route::post('/imcrm/evaluate-tier', [ApiController::class, 'evaluateTier'])->name('evaluateTier');
    Route::post('/imcrm/trigger-sic-workflow', [ApiController::class, 'triggerSICWorkflow'])->name('triggerSICWorkflow');
    Route::post('/imcrm/analyze-health', [ApiController::class, 'analyseHealthData']);
    Route::post('/imcrm/send-health-apply-now-email', [ApiController::class, 'sendHealthApplyNowEmail'])->name('sendHealthApplyNowEmail');
    // Route::post('/imcrm/fix-quote-status-date', [ApiController::class, 'fixQuoteStatusDate']);
    Route::post('/imcrm/event/quote-updated', [ApiController::class, 'quoteUpdated'])->name('quoteUpdated');
    Route::post('/imcrm/quotes/automate-aml-screening', [AMLController::class, 'automateQuoteAmlScreening'])->name('api.imcrm.automate-aml-screening');
    Route::post('/imcrm/trigger-sic-whatsapp', [ApiController::class, 'triggerSICWhatsapp'])->name('triggerSICWhatsapp');
    Route::post('/imcrm/run-cqf-jobs', [ApiController::class, 'runCQFJobs']);
    Route::post('/imcrm/trigger-conversion-optimization-scheduled-export', [ApiController::class, 'triggerConversionOptimizationScheduledExport'])
        ->name('triggerConversionOptimizationScheduledExport');

    // FTC email
    Route::post('ftc-email/{quoteType}/{uuid}/dispatch', [FtcEmailController::class, 'send'])->name('api.ftc-email.dispatch');

    // FTC email tracking routes
    Route::post('ftc/{quoteType}/{uuid}', [FtcEmailLogController::class, 'store']);
    Route::post('ftc', [FtcEmailLogController::class, 'update']);
    Route::get('/imcrm/quote/{quoteUuid}/{quoteType}/auto-capture-failed', [ApiController::class, 'markAutoCaptureFailed']);
    Route::post('/imcrm/home-sync-sal', [ApiController::class, 'homeSyncSAL'])->name('home-sync-sal');
    Route::post('duplicate-entires', [ApiController::class, 'duplicateEntries']);
    Route::post('/cache/forget', [ApiController::class, 'forgetCache']);
    Route::post('/imcrm/trigger-aig-workflow', [ApiController::class, 'triggerAIGWorkflow'])->name('triggerAIGWorkflow');
    // life
    Route::post('life/send-oca-email', [LifeController::class, 'sendOCAEmail'])->name('lifeSendOCAEmail');
    Route::post('/imcrm/trigger-travel-aig-workflow', [ApiController::class, 'triggerTravelAIGWorkflow'])->name('triggerTravelAIGWorkflow');

    Route::get('/home/renewal-ocb-attachment', [ApiController::class, 'homeRenewalOCBAttachment'])->name('homeRenewalOCBAttachment');
    Route::get('/quotes/{quoteType}/get-plans-pdf-url', [GenericLobController::class, 'getPlansPdfUrl'])->name('getPlansPdfUrl');
    Route::post('/imcrm/document-notification', [ApiController::class, 'documentNotification'])->name('documentNotification');
    Route::post('send-my-alfred-welcome-email', [GenericLobController::class, 'sendMyAlfredWelcomeEmail']);

    Route::get('generic-documents/{insuranceProviderId?}/{quoteType?}', [ApiController::class, 'getGenericDocuments']);

    // BOR (Broker on Record) API Routes
    Route::prefix('bor')->group(function () {

        Route::get('details/{bor_ref_id}', [BorController::class, 'getBorLog'])->name('bor.get-bor-log');
        Route::get('completion-email-trigger/{bor_ref_id}', [BorController::class, 'borCompletionEmailTrigger'])->name('bor.completion-email-trigger');
        Route::post('generate-pdf', [BorController::class, 'generatePdf'])->name('bor.generate-pdf');
        Route::post('upload-document', [BorController::class, 'uploadDocument'])->name('bor.upload-document');
        Route::delete('delete-document', [BorController::class, 'deleteDocument'])->name('bor.delete-document');

        Route::get('document-types', [BorController::class, 'getDocumentTypes'])->name('bor.get-document-types');
        // Signature routes
        Route::post('sign-document', [BorController::class, 'signDocument'])->name('bor.sign-document');
    });
    Route::post('/imcrm/claim/assign-quote', [ApiController::class, 'assignClaim'])->name('assignClaim');

    Route::post('check-document-upload-after-authorization', [ApiController::class, 'checkDocumentUploadAfterPayment']);
    // Missing docs reminder and verify missing docs routes
    Route::prefix('imcrm')->group(function () {
        Route::post('/missing-docs-reminder/{quoteUuid}', [ApiController::class, 'missingDocsReminder'])->name('missingDocsReminder');
        Route::get('/verify-missing-docs/{quoteUuid}/{quoteType}', [ApiController::class, 'verifyMissingDocs'])->name('verifyMissingDocs');
    });

    Route::post('/imcrm/life-sync-health-questionnaire', [ApiController::class, 'lifeSyncHealthQuestionnaire'])->name('life-sync-health-questionnaire');
    Route::post('/stp-advisor-notification', [ApiController::class, 'stpAdvisorNotification']);

    Route::post('/pc-customer-assignment', [ApiController::class, 'tagPcpCustomers'])
        ->name('pc-customer-assignment');
    Route::post('/remove-pc-qualified', [ApiController::class, 'removePcQualified'])->name('remove-pc-qualified');
    Route::post('/tag-pc-qualified', [ApiController::class, 'tagPrivateClients']);

    Route::post('/imcrm/debug/lead-ocr-comparison', [ApiController::class, 'getLeadOCRComparison'])->name('debug.car-documents');

    Route::get('/get-ep-workflow-data', [EmbeddedProductController::class, 'getEpWorkflowData'])->name('get.ep-workflow-data');
    Route::post('/ep-cancellation-callback', EpCancellationCallbackController::class)->name('api.ep-cancellation-callback');
    Route::post('/imcrm/debug/quote-documents/rewatermark', [ApiController::class, 'rewatermarkQuoteDocuments'])->name('debug.rewatermark-quote-documents');

    // !! Do not remove this route, it is used for debugging purposes and do not enable it in production without approval from the team !!.
    // Route::post('/imcrm/re-trigger-revival-followups', [ApiController::class, 'reTriggerRevivalFollowups'])->name('reTriggerRevivalFollowups');
    Route::post('/imcrm/re-trigger-revival-followups-with-date', [ApiController::class, 'reTriggerRevivalFollowupsWithDate'])->name('reTriggerRevivalFollowupsWithDate');

    // amt
    Route::post('amt/quotes/{quoteType}/documents/census-list-excel', [QuoteDocumentController::class, 'storeCensusListExcel']);

    // pre qualification advisor allocation
    Route::post('/imcrm/pqa-allocation', [ApiController::class, 'preQualificationAdvisorAllocation'])->name('preQualificationAdvisorAllocation');
    // pre qualification advisor allocation
    Route::prefix('pqa')->group(function () {
        Route::post('/allocation', [ApiController::class, 'preQualificationAdvisorAllocation'])->name('preQualificationAdvisorAllocation');
    });

});

Route::post('/imcrm/assign-quote', [ApiController::class, 'assignLeads'])->name('assign-leads');
Route::post('/imcrm/zero-plans-email', [ApiController::class, 'handleZeroPlansEmail']);
Route::post('/imcrm/sib-health-callback', [ApiController::class, 'sibHealthQuoteCallBack']);
Route::post('/imcrm/trigger-ep-retargeting-email', [EmbeddedProductController::class, 'triggerEpRetargetingEmail'])->name('trigger.ep-retargeting-email');
// Route::post('/customers/tag-private-clientss', [ApiController::class, 'tagPrivateClients'])->name('tagPrivateClientss');

Route::post('/inbound-emails-hook', [ApiController::class, 'inboundEmailsHook']);
Route::post('/bird-inbound-emails-hook', [ApiController::class, 'birdInboundEmailsHook']);
Route::post('/bird-outbound-emails-status', [ApiController::class, 'birdOutboundEmailsHook']);
Route::post('/bird-whatsapp-inbound-hook', [ApiController::class, 'birdWhatsappInboundHook']);
Route::post('/bird-whatsapp-outbound-hook', [ApiController::class, 'birdWhatsappOutboundHook']);
Route::post('/bird-whatsapp-interaction-hook', [ApiController::class, 'birdWhatsappInteractionHook']);
Route::post('/followups/emails/events/{quoteTypeId}/{uuid}', [ApiController::class, 'logFollowUpEvent']);
Route::post('/stop-followup/email-events/{flowType}/{uuid}', [ApiController::class, 'stopFollowUpEvent']);
Route::post('/quote/update-quote-status', [ApiController::class, 'updateQuoteStatus']);
Route::post('/email-status/update-customer-replied', [ApiController::class, 'updateCustomerRepliedStatus'])->name('updateCustomerRepliedStatus');
Route::post('/imcrm/eligible-for-revival-followups', [ApiController::class, 'eligibleForRevivalFollowups'])->name('eligibleForRevivalFollowups');

Route::prefix('v1')->group(function () {
    Route::post('/log-ep-email-statuses', [ApiController::class, 'logEpEmailStatuses'])->name('logEpEmailStatuses');

    Route::post('quotes/car/followup-started', [CarQuoteController::class, 'followupStarted']);
    Route::post('quotes/car/pause-resume-followup', [CarQuoteController::class, 'updatePauseAndResumeCounters']);
    Route::post('quotes/car/update-quote-status', [CarQuoteController::class, 'updateQuoteStatus']);

    Route::get('quotes/car/followup-leads', [CarQuoteController::class, 'getFollowupLeads']);
    Route::get('quotes/car', [CarQuoteController::class, 'index']);
    Route::post('quotes/car/{uuid}/update-lead-status', [CarQuoteController::class, 'updateLeadStatus']);
    Route::get('quotes/car/{uuid}/ocb-details', [CarQuoteController::class, 'getOcbDetails']);

    Route::post('quotes/{quoteType}/documents', [QuoteDocumentController::class, 'store']);
    Route::get('quotes/{quoteType}/{quoteUuid}/documents', [QuoteDocumentController::class, 'index']);
    Route::delete('quotes/{quoteType}/documents', [QuoteDocumentController::class, 'destroy']);
    Route::get('quotes/{quoteType}/document-types', [QuoteDocumentController::class, 'getQuoteDocumentsToReceive']);
    Route::post('quotes/{quoteType}/export-plans-pdf', [GenericLobController::class, 'exportPlansPdf'])->name('exportPlansPdf');
    Route::get('quotes/{quoteType}/export-plans-pdf-link', [GenericLobController::class, 'exportPlansPdfLink'])->name('exportPlansPdfLink');

    // BOR SSE
    Route::get('bor/details/sse/{bor_ref_id}', [BorController::class, 'getBorLogSSE'])->name('bor.get-bor-log-sse');

    Route::post('quotes/send-ocb-email', [GenericLobController::class, 'getQuoteForOCBEmail'])->name('getQuoteForOCBEmail');

    Route::get('quotes/{quoteTypeId}/{quoteId}/email-status/export', [ApiController::class, 'exportEmailStatusLogs'])->name('exportEmailStatusLogs');

    Route::get('quotes/car/{uuid}', [CarQuoteController::class, 'show']);
    Route::post('quotes/send-ep-certificate', [EmbeddedProductController::class, 'sendDocument'])->name('sendDocument');
    Route::post('activities/create', [ActivityController::class, 'createActivity'])->name('createActivity');
    Route::get('activities', [ActivityController::class, 'getActivity'])->name('getActivity');
    Route::get('/renewals/validation-failed-download/{id}', [ApiController::class, 'downloadValidationFailedFile'])->name('downloadValidationFailedFile');

    // User management routes
    Route::get('users/first-manager/{email}', [UserController::class, 'getFirstManager'])->name('getFirstManager');

    // upload to metlife API route
    Route::post('quotes/{quoteType}/upload-to-metlife', [QuoteDocumentController::class, 'handleMetLife']);
    Route::get('/failed-ila-emails/{quoteType}', [ApiController::class, 'exportFailedIlaLeads'])->name('export-failed-ila-leads');

    Route::post('quotes/send-zero-plans-email', [ApiController::class, 'sendZeroPlansEmail'])->name('sendZeroPlansEmail');
    Route::get('/claim-documents', [QuoteDocumentController::class, 'getClaimDocuments']);

    Route::post('/imcrm/update-revival-lead-source', [ApiController::class, 'updateRevivalLeadSource'])->name('updateRevivalLeadSource');
    Route::post('/imcrm/travel/retrigger-aml-screening', [AMLController::class, 'retriggerTravelAmlScreening'])->name('api.imcrm.travel.retrigger-aml-screening');
});

Route::post('/payments/update-payment-status', [ApiController::class, 'quotePaymentStatusUpdated']);

Route::get('/ken2-connectivity', [ApiController::class, 'Ken2Connectivity']);

// Cms Api Routes
Route::prefix('cms')->middleware(['basicAuth'])->group(function () {
    Route::group(['prefix' => 'health-plans'], function () {
        Route::post('/', [HealthPlanController::class, 'create']);
        Route::put('/{id}', [HealthPlanController::class, 'update']);
        Route::get('/get-status-versions/{parentId}/{status}', [HealthPlanController::class, 'getStatusVersions']);
        Route::delete('/{id}', [HealthPlanController::class, 'delete']);
        Route::post('/publish/{id}', [HealthPlanController::class, 'publish']);
    });

    Route::prefix('health-rates')->group(function () {
        Route::post('/', [HealthRateController::class, 'create']);
        Route::put('/{id}', [HealthRateController::class, 'update']);
        Route::delete('/{id}', [HealthRateController::class, 'delete']);
        Route::delete('/sheet/{id}', [HealthRateControlController::class, 'delete']);
        Route::post('/publish/{id}', [HealthRateController::class, 'publish']);
    });
});

Route::get('/heath-check', function () {
    return response()->json(['success' => true]);
});
