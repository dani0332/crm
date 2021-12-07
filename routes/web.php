<?php

use App\Http\Controllers\AgeDiscountController;
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
use App\Http\Controllers\BaseDiscountController;
use App\Http\Controllers\BulkEmailProcessController;
use App\Http\Controllers\CRUDController;
use App\Http\Controllers\FtcFormController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\UploadResourceController;
use App\Http\Controllers\ValuationController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\InsuranceCompanyController;
use App\Http\Controllers\HandlerController;
use App\Http\Controllers\LeadAssignmentController;
use App\Http\Controllers\LeadSearchController;
use App\Http\Controllers\MyLeadsController;
use App\Http\Controllers\ReasonController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\PaymentModeController;
use App\Http\Controllers\RenewalDataProcessingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\VehicleDepreciationController;
use App\Models\DiscountEngineBase;
use App\Http\Controllers\TmInsuranceTypeController;
use App\Http\Controllers\TmCallStatusController;
use App\Http\Controllers\TmLeadStatusController;
use App\Http\Controllers\TmLeadController;
use App\Http\Controllers\TmUploadLeadController;
use App\Http\Controllers\RenewalsUploadController;
use App\Http\Controllers\RewardSliderController;
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

Route::middleware(['auth'])->get('/home', function () {
    return view('home');
});


Route::group(['middleware' =>  ['auth']], function () {
    Route::resource('myleads', MyLeadsController::class)->middleware('CheckRole:ADMIN,MANAGER,HEALTH_ADVISOR,BUSINESS_ADVISOR,TRAVEL_ADVISOR,LIFE_ADIVSOR,HOME_ADVISOR,CAR_ADVISOR');
    Route::resource('leadsearch', LeadSearchController::class)->names([
        'index' => 'leadsearch.index',
        'create' => 'leadsearch.create',
        'store' => 'leadsearch.store',
        'show' => 'leadsearch.show',
        'edit' => 'leadsearch.edit',
        'update' => 'leadsearch.update',
        'destroy' => 'leadsearch.destroy',
    ])->middleware('CheckRole:ADMIN,MANAGER,HEALTH_ADVISOR,BUSINESS_ADVISOR,TRAVEL_ADVISOR,LIFE_ADIVSOR,HOME_ADVISOR,CAR_ADVISOR');
    Route::resource('leadassignment', LeadAssignmentController::class)->names([
        'index' => 'leadassignment.index',
        'create' => 'leadassignment.create',
        'store' => 'leadassignment.store',
        'show' => 'leadassignment.show',
        'edit' => 'leadassignment.edit',
        'update' => 'leadassignment.update',
        'destroy' => 'leadassignment.destroy',
    ]);
    Route::post('manualLeadAssign', [LeadAssignmentController::class, 'manualLeadAssign'])->name('manualAssignment');
    Route::get('getAdvisors', [LeadAssignmentController::class, 'getAdvisors'])->name('getAdvisors');
    Route::get('getTeamManagers', [UserController::class, 'getTeamManagers'])->name('getTeamManagers');
    Route::resource('customer', CustomerController::class);
    Route::get('/customer-upload', [CustomerController::class, 'uploadCustomers']);
    Route::post('/customer-process', [CustomerController::class, 'processCustomerCSV']);

    Route::resource('renewals', RenewalsUploadController::class);
    Route::get('/renewals-list', [RenewalsUploadController::class, 'index']);
    Route::get('/renewals-upload', [RenewalsUploadController::class, 'uploadRenewals']);
    Route::post('/renewals-process', [RenewalsUploadController::class, 'processRenewalsCSV']);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('dashboard-stats', [DashboardController::class, 'dashboardStats']);
    Route::group(['prefix' => 'rewards'], function () {
        Route::resource('partner', PartnerController::class);
        Route::resource('reward', RewardController::class);
        Route::resource('reward-categories', RewardCategoryController::class);
        Route::resource('reward-tags', RewardTagController::class);
        Route::resource('reward.reward-translation', RewardTranslationController::class);
        Route::resource('reward-sliders', RewardSliderController::class)->middleware('permission:reward-sliders-list|reward-sliders-create|reward-sliders-edit|reward-sliders-delete');
    });

    Route::group(['prefix' => 'admin'], function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
    });

    Route::group(['prefix' => 'quotes'], function () {
        Route::resource('carquotes', CarQuoteController::class);
        Route::get('carquotes/car_resubmit/{id}', [CarQuoteController::class, 'car_resubmit_capi'])->name('car_resubmit_capi');
        Route::resource('healthquotes', HealthQuoteController::class);
        Route::resource('health', CRUDController::class);
        Route::resource('car', CRUDController::class);
        Route::resource('life', CRUDController::class);
        Route::resource('home', CRUDController::class);
        Route::resource('business', CRUDController::class);
        Route::resource('travel', CRUDController::class);
        Route::resource('teams', CRUDController::class);
        Route::resource('leadstatus', CRUDController::class);
        Route::post('save', [CRUDController::class, 'store'])->name('saveQuote');
        Route::post('update', [CRUDController::class, 'update'])->name('updateQuote');
        Route::get('getvalues/{modelType}/{propertyName}/{recordId}', [CRUDController::class, 'getDropdownSourceNameForDisplay']);
        Route::get('car/{quoteId}/plan_details/{planId}', [CRUDController::class, 'plan_details'])->name('plan_details');
        Route::get('manualLeadAssign', [CRUDController::class, 'manualLeadAssign'])->name('manualLeadAssign');
    });

    Route::group(['prefix' => 'transapp'], function () {
        Route::resource('insurancecompany', InsuranceCompanyController::class);
        Route::resource('handler', HandlerController::class);
        Route::resource('reason', ReasonController::class);
        Route::resource('status', StatusController::class);
        Route::resource('paymentmode', PaymentModeController::class);
        Route::resource('transaction', TransactionController::class);
        Route::get('home', [TransactionController::class, 'transectionHome'])->name('home');
        Route::get('showtransaction', [TransactionController::class, 'showTransaction'])->name('showtransaction');
        Route::get('re-issue-transaction', [TransactionController::class, 'cancelAndReIssueTransectionView'])->name('reissue_view');
        Route::get('re-issue-transaction-form', [TransactionController::class, 'cancelAndReIssueTransectionForm'])->name('re_issue_transaction_form');
        Route::post('re-issue-transaction', [TransactionController::class, 'cancelAndReIssueTransection'])->name('re_issue');
        Route::get('cancel-transaction', [TransactionController::class, 'cancelAndReIssueTransectionView'])->name('cancel_view');
        Route::get('cancel-transaction-form', [TransactionController::class, 'cancelAndReIssueTransectionForm'])->name('cancel_transaction_form');
        Route::post('cancel-transaction', [TransactionController::class, 'cancelAndReIssueTransection'])->name('cancel');
    });
    Route::group(['prefix' => 'valuation'], function () {
        Route::get('calculatevaluation', [ValuationController::class, 'calculateValuation'])->name('calculatevaluation');
        Route::resource('vehicledepreciation', VehicleDepreciationController::class);
    });
    Route::get('/valuation/car-models', [ValuationController::class, 'carModelBasedOnCarMake']);
    Route::get('/valuation/car-model-detail', [ValuationController::class, 'carTrimBasedOnCarModel']);

    Route::group(['prefix' => 'claim'], function () {
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
        Route::get('aml/{quoteTypeId}/details/{quoteRequestId}', [AMLController::class, 'amlQuoteDetails']);
        Route::get('aml/{quoteTypeId}/details/{quoteRequestId}/quoteStatusUpdate/{quoteTypeCode}', [AMLController::class, 'quoteStatusUpdate'])->name('quoteStatusUpdate');
        Route::get('aml/{quoteTypeId}/details/{quoteRequestId}/quoteUpdate', [AMLController::class, 'quoteUpdate'])->name('quoteUpdate');
        Route::get('aml/download/history', [AMLController::class, 'sanctionListHistory'])->name('sanctionListHistory');
        Route::get('aml/upload/uae', [AMLController::class, 'uploadUaeSanctionList'])->name('uploadUaeSanctionList');
        Route::post('aml/upload/uae-list', [AMLController::class, 'uaeSanctionListUpload'])->name('uaeSanctionListUpload');
    });


    Route::group(['prefix' => 'discount'], function () {
        Route::resource('base', BaseDiscountController::class);
        Route::resource('age', AgeDiscountController::class);
        // Route::get('create',[DiscountEngineBaseController::class,'create']);
        // Route::post('submit',[DiscountEngineBaseController::class,'store']);
    });

    Route::group(['prefix' => 'telemarketing'], function () {
        Route::resource('tmleads', TmLeadController::class);
        Route::resource('tminsurancetype', TmInsuranceTypeController::class);
        Route::resource('tmcallstatus', TmCallStatusController::class);
        Route::resource('tmleadstatus', TmLeadStatusController::class);
        Route::get('/car-model', [TmLeadController::class, 'carModelBasedOnCarMake']);
        Route::resource('tmuploadlead', TmUploadLeadController::class);
        Route::get('tmleads/{tmLeadID}/tmLeadUpdate', [TmLeadController::class, 'tmLeadUpdate'])->name('tmLeadUpdate');
        Route::get('/tmLeadsAssign', [TmLeadController::class, 'tmLeadsAssign']);
    });

    Route::get('/car-model', [ClaimController::class, 'carModelBasedOnCarMake']);
    Route::post('auditable', [AuditableController::class, 'loadAuditableComponent']);
});



Route::POST('/sendBulkWelcomeEmails', [BulkEmailProcessController::class, 'ProcessBulkWelcomeEmails'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);


/***** RestAPI */

Route::group(['middleware' =>  ['auth.rest']], function () use ($router) {
    Route::resource('ftcform', FtcFormController::class);
    Route::resource('assignOE', FtcFormController::class);
    Route::group(['prefix' => 'form'], function () {
        Route::GET('/{form}', [FormController::class, 'index']);
        Route::GET('/{form}/{form_id}', [FormController::class, 'getFormDetail']);
        Route::PUT('/{form}/{form_id}', [FormController::class, 'update']);
        Route::DELETE('/{form}/{form_id}', [FormController::class, 'delete']);
        Route::POST('/{form}', [FormController::class, 'save']);
    });
    // Route::POST('/sendReviewEmail', [FormController::class,'sendReviewEmail'])
    //         ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;




    Route::group(['prefix' => 'users'], function () use ($router) {
        Route::GET('/me', [UserController::class, 'me']);
    });

    Route::group(['prefix' => 'resource'], function () use ($router) {
        Route::POST('/store', [UploadResourceController::class, 'store']);
    });
});

Route::POST('/processInslyRenewalData', [RenewalDataProcessingController::class, 'FetchAndProcessInslyData'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
