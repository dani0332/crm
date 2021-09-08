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
use App\Http\Controllers\ClaimsAttachmentsController;
use App\Http\Controllers\AMLController;
use App\Http\Controllers\BulkEmailProcessController;
use App\Http\Controllers\FtcFormController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\UploadResourceController;
use App\Http\Controllers\ValuationController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\InsuranceCompanyController;
use App\Http\Controllers\HandlerController;
use App\Http\Controllers\ReasonController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\PaymentModeController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\VehicleDepreciationController;
use App\Http\Controllers\TmInsuranceTypeController;
use App\Http\Controllers\TmCallStatusController;
use App\Http\Controllers\TmLeadStatusController;
use App\Http\Controllers\TmLeadController;
use App\Http\Controllers\TmUploadLeadController;
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

Route::get('/debug-sentry', function () {
    throw new Exception('My first Sentry error!');
});

Route::get('auth/google', 'App\Http\Controllers\GoogleSocialiteController@redirectToGoogle');
Route::get('google/callback', 'App\Http\Controllers\GoogleSocialiteController@handleCallback');

Route::middleware(['auth'])->get('/home', function() {
    return view('home');
});

Route::group(['middleware' =>  ['auth']], function() {

    Route::resource('customer', CustomerController::class);
    Route::get('/dashboard',[DashboardController::class, 'index'])->name('dashboard');

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
        Route::get('carquotes/car_resubmit/{id}',[CarQuoteController::class,'car_resubmit_capi'])->name('car_resubmit_capi');
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
    Route::group(['prefix' => 'valuation'], function () {
        Route::get('calculatevaluation',[ValuationController::class,'calculateValuation'])->name('calculatevaluation');
        Route::resource('vehicledepreciation', VehicleDepreciationController::class);
    });
    Route::get('/valuation/car-models',[ValuationController::class,'carModelBasedOnCarMake']);
    Route::get('/valuation/car-model-detail',[ValuationController::class,'carTrimBasedOnCarModel']);

    Route::group(['prefix' => 'claim'], function() {
        Route::resource('claims', ClaimController::class);
        Route::resource('typeofinsurance', TypeOfInsuranceController::class);
        Route::resource('subtypeofinsurance', SubTypeOfInsuranceController::class);
        Route::resource('claimsstatus', ClaimsStatusController::class);
        Route::resource('carrepaircoverage', CarRepairCoverageController::class);
        Route::resource('carrepairtype', CarRepairTypeController::class);
        Route::resource('rentacar', RentACarController::class);
        Route::resource('claims.claim-attachment', ClaimsAttachmentsController::class);
    });

    Route::group(['prefix' => 'kyc'], function () {
        Route::resource('aml', AMLController::class);
        Route::get('aml/{quoteTypeId}/details/{quoteRequestId}' , [AMLController::class,'amlQuoteDetails']);
        Route::get('aml/{quoteTypeId}/details/{quoteRequestId}/quoteStatusUpdate/{quoteTypeCode}',[AMLController::class,'quoteStatusUpdate'])->name('quoteStatusUpdate');
    });

    Route::group(['prefix' => 'telemarketing'], function() {
        Route::resource('tmleads', TmLeadController::class);
        Route::resource('tminsurancetype', TmInsuranceTypeController::class);
        Route::resource('tmcallstatus', TmCallStatusController::class);
        Route::resource('tmleadstatus', TmLeadStatusController::class);
        Route::get('/car-model',[TmLeadController::class,'carModelBasedOnCarMake']);
        Route::resource('tmuploadlead', TmUploadLeadController::class);
        Route::get('tmuploadlead/{tmUploadLeadId}/tmUploadLeadsProcess', [TmUploadLeadController::class,'tmUploadLeadsProcess'])->name('tmUploadLeadsProcess');
    });

    Route::get('/car-model',[ClaimController::class,'carModelBasedOnCarMake']);
    Route::post('auditable', [AuditableController::class, 'loadAuditableComponent']);
});



Route::POST('/sendBulkWelcomeEmails', [BulkEmailProcessController::class,'ProcessBulkWelcomeEmails'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);


/***** RestAPI */

Route::group(['middleware' =>  ['auth.rest']], function() use($router) {
Route::resource('ftcform', FtcFormController::class);
Route::group(['prefix' => 'form'], function()  {
    Route::GET('/{form}', [FormController::class,'index']);
    Route::GET('/{form}/{form_id}', [FormController::class,'getFormDetail']);
    Route::PUT('/{form}/{form_id}', [FormController::class,'update']);
    Route::DELETE('/{form}/{form_id}', [FormController::class,'delete']);
    Route::POST('/{form}', [FormController::class,'save']);
});
// Route::POST('/sendReviewEmail', [FormController::class,'sendReviewEmail'])
//         ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;

Route::group(['prefix' => 'users'], function()use($router) {
    Route::GET('/me', [UserController::class,'me']);
});

Route::group(['prefix' => 'resource'], function()use($router) {
    Route::POST('/store', [UploadResourceController::class,'store']);
});
});
