<?php

use App\Http\Controllers\AuditableController;
use App\Http\Controllers\CarQuoteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthQuoteController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\RewardCategoryController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\RewardTagController;
use App\Http\Controllers\RewardTranslationController;
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

use App\Http\Controllers\UserController;
use App\Http\Controllers\InsuranceCompanyController;
use App\Http\Controllers\HandlerController;
use App\Http\Controllers\ReasonController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\PaymentModeController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

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

Route::get('auth/google', 'App\Http\Controllers\GoogleSocialiteController@redirectToGoogle');
Route::get('google/callback', 'App\Http\Controllers\GoogleSocialiteController@handleCallback');

Route::middleware(['auth:sanctum', 'verified'])
    ->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::post('dashboard-stats', [DashboardController::class, 'dashboardStats']);
Route::group(['prefix' => 'rewards'], function () {
    Route::resource('partner', PartnerController::class);
    Route::resource('reward', RewardController::class);
    Route::resource('reward-categories', RewardCategoryController::class);
    Route::resource('reward-tags', RewardTagController::class);
    Route::resource('reward.reward-translation', RewardTranslationController::class);
});

Route::group(['prefix' => 'admin'], function () {
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
});

Route::group(['prefix' => 'quotes'], function () {
    Route::resource('carquotes', CarQuoteController::class);
    Route::POST('carquotes/resubmit_api', [CarQuoteController::class, 'resubmitApi']);
    Route::resource('healthquotes', HealthQuoteController::class);
});

Route::group(['prefix' => 'transapp'], function () {
    Route::resource('insurancecompany', InsuranceCompanyController::class);
    Route::resource('handler', HandlerController::class);
    Route::resource('reason',ReasonController::class);
    Route::resource('status',StatusController::class);
    Route::resource('paymentmode',PaymentModeController::class);
    Route::resource('transaction',TransactionController::class);
    Route::get('home',[TransactionController::class,'transectionHome'])->name('home');
    Route::get('showtransaction',[TransactionController::class,'showTransaction'])->name('showtransaction');
    Route::get('re-issue-transaction',[TransactionController::class,'cancelAndReIssueTransectionView'])->name('reissue_view');
    Route::get('re-issue-transaction-form',[TransactionController::class,'cancelAndReIssueTransectionForm'])->name('re_issue_transaction_form');
    Route::post('re-issue-transaction',[TransactionController::class,'cancelAndReIssueTransection'])->name('re_issue');
    Route::get('cancel-transaction',[TransactionController::class,'cancelAndReIssueTransectionView'])->name('cancel_view');
    Route::get('cancel-transaction-form',[TransactionController::class,'cancelAndReIssueTransectionForm'])->name('cancel_transaction_form');
    Route::post('cancel-transaction',[TransactionController::class,'cancelAndReIssueTransection'])->name('cancel');

});

Route::resource('customer', CustomerController::class);

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

Route::get('/car-model',[ClaimController::class,'carModelBasedOnCarMake']);
Route::post('auditable', [AuditableController::class, 'loadAuditableComponent']);
