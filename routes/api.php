<?php

use App\Http\Controllers\API\ApiController;
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
    Route::post('/imcrm/sib-flow', [ApiController::class, 'triggerSibFlow']);
});
Route::get('/imcrm/sib-health-callback', [ApiController::class, 'sibHealthQuoteCallBack']);
