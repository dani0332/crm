<?php

use App\Http\Controllers\API\ApiController;
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
    // TODO: remove the comment on production
    //Route::post('/imcrm/sib-flow', [ApiController::class, 'triggerSibFlow']);
});
// TODO: remove the comment on production
//Route::post('/imcrm/sib-health-callback', [ApiController::class, 'sibHealthQuoteCallBack']);

Route::prefix('v1')->group(function () {
    Route::post('quotes/{quoteType}/documents', [QuoteDocumentController::class, 'store']);
    Route::get('quotes/{quoteType}/{quoteUuid}/documents', [QuoteDocumentController::class, 'index']);
    Route::delete('quotes/{quoteType}/documents', [QuoteDocumentController::class, 'destroy']);
    Route::get('quotes/{quoteType}/document-types', [QuoteDocumentController::class, 'getQuoteDocumentsToReceive']);
});
