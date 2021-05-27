<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\RewardCategoryController;
use App\Http\Controllers\RewardTagController;
use App\Http\Controllers\RewardTranslationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CarQouteController;
use App\Http\Controllers\HealthQouteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AuditableController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('login');
});

Route::get('auth/google','App\Http\Controllers\GoogleSocialiteController@redirectToGoogle');
Route::get('google/callback', 'App\Http\Controllers\GoogleSocialiteController@handleCallback');

Route::middleware(['auth:sanctum', 'verified'])->get('/home', function () {
    return view('home');
})->name('home');


Route::group(['prefix' => 'rewards'], function() {
    Route::resource('partner', PartnerController::class);
    Route::resource('reward', RewardController::class);
    Route::resource('reward-categories', RewardCategoryController::class);
    Route::resource('reward-tags', RewardTagController::class);
    Route::resource('reward.reward-translation', RewardTranslationController::class);
});

Route::group(['prefix' => 'admin'], function() {
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
});


Route::group(['prefix' => 'qoutes'], function() {
    Route::resource('carqoutes', CarQouteController::class);
    Route::POST('carqoutes/resubmit_api', [ CarQouteController::class , 'resubmitApi']);
    Route::resource('healthqoutes', HealthQouteController::class);
});

Route::resource('customer', CustomerController::class);

Route::post('auditable',[AuditableController::class,'loadAuditableComponent']);

