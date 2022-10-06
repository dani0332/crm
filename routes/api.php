<?php

use App\Http\Controllers\API\ApiController;
use App\Http\Controllers\API\V1\QuoteDocumentController;
use App\Http\Controllers\API\V1\CarQuoteRequestController;
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
    //TODO:
    Route::post('/imcrm/sib-flow', [ApiController::class, 'triggerSibFlow']);
});
//TODO:
Route::post('/imcrm/sib-health-callback', [ApiController::class, 'sibHealthQuoteCallBack']);
Route::prefix('v1')->group(function () {
    Route::post('quotes/{quoteType}/documents', [QuoteDocumentController::class, 'store']);
    Route::get('quotes/{quoteType}/{quoteUuid}/documents', [QuoteDocumentController::class, 'index']);
    Route::delete('quotes/{quoteType}/documents', [QuoteDocumentController::class, 'destroy']);
    Route::get('quotes/{quoteType}/document-types', [QuoteDocumentController::class, 'getQuoteDocumentsToReceive']);
    Route::post('quotes/{quoteType}/export-plans-pdf', [CarQuoteRequestController::class, 'exportPlansPdf'])->name('exportPlansPdf');
});
