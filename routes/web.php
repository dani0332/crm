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
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\TypeOfInsuranceController;
use App\Http\Controllers\SubTypeOfInsuranceController;
use App\Http\Controllers\ClaimsStatusController;
use App\Http\Controllers\CarRepairCoverageController;
use App\Http\Controllers\CarRepairTypeController;
use App\Http\Controllers\RentACarController;


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

Route::group(['prefix' => 'claim'], function() {
    Route::resource('claims', ClaimController::class);
});
Route::group(['prefix' => 'claim'], function() {
    Route::resource('typeofinsurance', TypeOfInsuranceController::class);
});
Route::group(['prefix' => 'claim'], function() {
    Route::resource('subtypeofinsurance', SubTypeOfInsuranceController::class);
});
Route::group(['prefix' => 'claim'], function() {
    Route::resource('claimsstatus', ClaimsStatusController::class);
});
Route::group(['prefix' => 'claim'], function() {
    Route::resource('carrepaircoverage', CarRepairCoverageController::class);
});
Route::group(['prefix' => 'claim'], function() {
    Route::resource('carrepairtype', CarRepairTypeController::class);
});
Route::group(['prefix' => 'claim'], function() {
    Route::resource('rentacar', RentACarController::class);
});
Route::get('/car-model',function () {
$cat_id = Request::get('cat_id');
$subcategories = DB::table('car_model')->where('car_make_code','=',$cat_id)->get(array('id','text','code'));
return Response::json($subcategories);});