<?php

use App\Http\Controllers\API\ActivityController;
use App\Http\Controllers\API\ApiController;
use App\Http\Controllers\API\V1\CarQuoteController;
use App\Http\Controllers\API\V1\EmbeddedProductController;
use App\Http\Controllers\API\V1\GenericLobController;
use App\Http\Controllers\API\V1\QuoteDocumentController;
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
});
Route::post('/imcrm/assign-quote', [ApiController::class, 'assignLeads']);
Route::post('/imcrm/zero-plans-email', [ApiController::class, 'handleZeroPlansEmail']);
Route::post('/imcrm/sib-health-callback', [ApiController::class, 'sibHealthQuoteCallBack']);

Route::post('/inbound-emails-hook', [ApiController::class, 'inboundEmailsHook']);
Route::post('/bird-inbound-emails-hook', [ApiController::class, 'birdInboundEmailsHook']);
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
    Route::post('quotes/send-ocb-email', [GenericLobController::class, 'getQuoteForOCBEmail'])->name('getQuoteForOCBEmail');

    Route::get('quotes/car/{uuid}', [CarQuoteController::class, 'show']);
    Route::post('quotes/send-ep-certificate', [EmbeddedProductController::class, 'sendDocument'])->name('sendDocument');
    Route::post('activities/create', [ActivityController::class, 'createActivity'])->name('createActivity');
    Route::get('activities', [ActivityController::class, 'getActivity'])->name('getActivity');
});
Route::post('/payments/update-payment-status', [ApiController::class, 'quotePaymentStatusUpdated']);

Route::get('/ken2-connectivity', [ApiController::class, 'Ken2Connectivity']);
