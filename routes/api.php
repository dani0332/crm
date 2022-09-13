<?php

use App\Http\Controllers\API\ApiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\QuoteDocumentController;
use App\Http\Controllers\API\V1\DocumentTypeController;

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
    Route::post('/imcrm/sib-flow', [ApiController::class, 'triggerSibFlow']);
});

Route::post('/imcrm/sib-health-callback/{uuid}', [ApiController::class, 'sibHealthQuoteCallBack']);

Route::prefix('v1')->group(function ()
{
    Route::post('quotes/{type}/documents',      [QuoteDocumentController::class, 'store']);
    Route::get('quotes/{type}/document-types',  [QuoteDocumentController::class, 'getQuoteDocumentsToReceive']);
});


