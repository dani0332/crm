<?php

use App\Http\Controllers\API\ActivityController;
use App\Http\Controllers\API\ApiController;
use App\Http\Controllers\API\V1\BorController;
use App\Http\Controllers\API\V1\CarQuoteController;
use App\Http\Controllers\API\V1\EmbeddedProductController;
use App\Http\Controllers\API\V1\FtcEmailLogController;
use App\Http\Controllers\API\V1\GenericLobController;
use App\Http\Controllers\API\V1\LifeController;
use App\Http\Controllers\API\V1\QuoteDocumentController;
use App\Http\Controllers\UserController;
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
});

Route::prefix('v1')->middleware(['basicAuth'])->group(function () {
    Route::post('/imcrm/evaluate-tier', [ApiController::class, 'evaluateTier'])->name('evaluateTier');
    Route::post('/imcrm/trigger-sic-workflow', [ApiController::class, 'triggerSICWorkflow'])->name('triggerSICWorkflow');
    Route::post('/imcrm/analyze-health', [ApiController::class, 'analyseHealthData']);
    Route::post('/imcrm/send-health-apply-now-email', [ApiController::class, 'sendHealthApplyNowEmail'])->name('sendHealthApplyNowEmail');
    // Route::post('/imcrm/fix-quote-status-date', [ApiController::class, 'fixQuoteStatusDate']);
    Route::post('/imcrm/event/quote-updated', [ApiController::class, 'quoteUpdated'])->name('quoteUpdated');
    Route::post('/imcrm/trigger-sic-whatsapp', [ApiController::class, 'triggerSICWhatsapp'])->name('triggerSICWhatsapp');

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
    Route::post('/imcrm/document-notification', [ApiController::class, 'documentNotification'])->name('documentNotification');

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

});
Route::post('/imcrm/assign-quote', [ApiController::class, 'assignLeads']);
Route::post('/imcrm/zero-plans-email', [ApiController::class, 'handleZeroPlansEmail']);
Route::post('/imcrm/sib-health-callback', [ApiController::class, 'sibHealthQuoteCallBack']);
Route::post('/customers/tag-private-clientss', [ApiController::class, 'tagPrivateClientss'])->name('tagPrivateClientss');

Route::post('/inbound-emails-hook', [ApiController::class, 'inboundEmailsHook']);
Route::post('/bird-inbound-emails-hook', [ApiController::class, 'birdInboundEmailsHook']);
Route::post('/bird-outbound-emails-status', [ApiController::class, 'birdOutboundEmailsHook']);
Route::post('/followups/emails/events/{quoteTypeId}/{uuid}', [ApiController::class, 'logFollowUpEvent']);
Route::post('/stop-followup/email-events/{flowType}/{uuid}', [ApiController::class, 'stopFollowUpEvent']);
Route::post('/quote/update-quote-status', [ApiController::class, 'updateQuoteStatus']);

Route::prefix('v1')->group(function () {

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

    // User management routes
    Route::get('users/first-manager/{email}', [UserController::class, 'getFirstManager'])->name('getFirstManager');

});
Route::post('/payments/update-payment-status', [ApiController::class, 'quotePaymentStatusUpdated']);

Route::get('/ken2-connectivity', [ApiController::class, 'Ken2Connectivity']);

Route::get('/heath-check', function () {
    return response()->json(['success' => true]);
});
