<?php

use App\Http\Controllers\API\ApiController;
use App\Http\Controllers\API\V1\GenericLobController;
use App\Http\Controllers\API\V1\QuoteDocumentController;
use App\Http\Controllers\V2\EmbeddedProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\CarQuoteController;

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
Route::post('/imcrm/assign-quote', [ApiController::class, 'assignLeads']);
Route::post('/imcrm/sib-health-callback', [ApiController::class, 'sibHealthQuoteCallBack']);

Route::prefix('v1')->group(function () {

    Route::post('quotes/car/followup-started', [CarQuoteController::class, 'followupStarted']);
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
});
