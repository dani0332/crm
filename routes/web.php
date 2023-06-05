<?php

use App\Enums\quoteTypeCode;
use App\Http\Controllers\ActivitesController;
use App\Http\Controllers\AgeDiscountController;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\AMLController;
use App\Http\Controllers\AMTController;
use App\Http\Controllers\AuditableController;
use App\Http\Controllers\BaseDiscountController;
use App\Http\Controllers\BulkEmailProcessController;
use App\Http\Controllers\BusinessQuoteController;
use App\Http\Controllers\CarLeadAllocationController;
use App\Http\Controllers\CarRepairCoverageController;
use App\Http\Controllers\CarRepairTypeController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ClaimsAttachmentsController;
use App\Http\Controllers\ClaimsStatusController;
use App\Http\Controllers\CRUDController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FailedJobsController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\GenericCrudController;
use App\Http\Controllers\HandlerController;
use App\Http\Controllers\HealthQuoteController;
use App\Http\Controllers\InsuranceCompanyController;
use App\Http\Controllers\LeadAllocationController;
use App\Http\Controllers\LeadAssignmentController;
use App\Http\Controllers\MembersDetailController;
use App\Http\Controllers\PaymentModeController;
use App\Http\Controllers\QuoteDocumentController;
use App\Http\Controllers\ReasonController;
use App\Http\Controllers\RenewalDataProcessingController;
use App\Http\Controllers\RenewalsUploadController;
use App\Http\Controllers\RentACarController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\SubTypeOfInsuranceController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TmCallStatusController;
use App\Http\Controllers\TmInsuranceTypeController;
use App\Http\Controllers\TmLeadController;
use App\Http\Controllers\TmLeadStatusController;
use App\Http\Controllers\TmUploadLeadController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\TravelMembersDetailController;
use App\Http\Controllers\TypeOfInsuranceController;
use App\Http\Controllers\UploadResourceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\V2\ActivityController;
use App\Http\Controllers\V2\AmtController as V2AmtController;
use App\Http\Controllers\V2\BikeQuoteController;
use App\Http\Controllers\V2\CarQuoteController;
use App\Http\Controllers\V2\CentralController;
use App\Http\Controllers\V2\CycleQuoteController;
use App\Http\Controllers\V2\EmbeddedProductController;
use App\Http\Controllers\V2\JetskiQuoteController;
use App\Http\Controllers\V2\LifeQuoteController;
use App\Http\Controllers\V2\PersonalPlanController;
use App\Http\Controllers\V2\PersonalQuoteController;
use App\Http\Controllers\V2\PetQuoteController;
use App\Http\Controllers\V2\YachtQuoteController;
use App\Http\Controllers\ValuationController;
use App\Http\Controllers\VehicleDepreciationController;
use Illuminate\Support\Facades\Artisan;
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

Route::get('/get-tier-users/{tierId}', [LeadAllocationController::class, 'getTierUsers']);

Route::get('auth/google', 'App\Http\Controllers\GoogleSocialiteController@redirectToGoogle');
Route::get('google/callback', 'App\Http\Controllers\GoogleSocialiteController@handleCallback');

Route::group(['middleware' => ['auth', 'last_login_check']], function () {
    Route::get('leadsearch', function () {
        return redirect('home');
    });

    Route::get('home', function () {
        return view('home');
    });
    Route::post('/reports/fetch-advisor-assigned-leads-data', [ReportsController::class, 'fetchAdvisorAssignedLeadsData'])->name('fetch-advisor-assigned-leads-data');
    Route::post('/reports/fetch-advisor-by-team', [ReportsController::class, 'fetchAdvisorListByTeam']);

    Route::get('/personal-quotes/car/car-quotes-search', [\App\Http\Controllers\V2\CarQuoteController::class, 'index'])->name('car-quotes-search');

    Route::group(['middleware' => ['check_route_access']], function () {
        Route::get('/accumulative-dashboard', [DashboardController::class, 'renderMainDashboard'])->name('main-dashboard-view');
        Route::get('/tpl-conversion-dashboard', [DashboardController::class, 'renderTplDashboard'])->name('tpl-dashboard-view');
        Route::get('/comprehensive-conversion-dashboard', [DashboardController::class, 'renderComprehensiveDashboard'])->name('comprehensive-dashboard-view');
        Route::get('/reports/advisor-conversion', [ReportsController::class, 'renderAdvisorConversionReport'])->name('advisor-conversion-report-view');
        Route::get('/reports/lead-distribution', [ReportsController::class, 'renderLeadDistributionReport'])->name('lead-distribution-report-view');
        Route::get('/reports/advisor-distribution', [ReportsController::class, 'renderAdvisorDistributionReport'])->name('advisor-distribution-report-view');
        Route::get('/reports/advisor-performance', [ReportsController::class, 'renderAdvisorPerformanceReport'])->name('advisor-performance-report-view');


        if (in_array(quoteTypeCode::Pet, newUi())) {
            Route::resource('personal-quotes/pet', PetQuoteController::class)->names(generateRouteNames('pet-quotes'));
        }
        if (in_array(quoteTypeCode::Bike, newUi())) {
            Route::resource('personal-quotes/bike', BikeQuoteController::class)->names(generateRouteNames('bike-quotes'));
        }
        if (in_array(quoteTypeCode::Cycle, newUi())) {
            Route::resource('personal-quotes/cycle', CycleQuoteController::class)->names(generateRouteNames('cycle-quotes'));
        }
        if (in_array(quoteTypeCode::Yacht, newUi())) {
            Route::resource('personal-quotes/yacht', YachtQuoteController::class)->names(generateRouteNames('yacht-quotes'));
        }
        if (in_array(quoteTypeCode::Jetski, newUi())) {
            Route::resource('personal-quotes/jetski', JetskiQuoteController::class)->names(generateRouteNames('jetski-quotes'));
        }

        if (in_array(quoteTypeCode::Life, newUi())) {
            Route::get('quotes/life/cards', [LifeQuoteController::class, 'cardsView'])->name('life-quotes-list');
            Route::resource('quotes/life', LifeQuoteController::class)->names(generateRouteNames('life-quotes'));
        }
        Route::resource('customer', CustomerController::class)->names(generateRouteNames('customers'));
        Route::get('{quoteType}/leads-export', [CentralController::class, 'exportLeads'])->name('data-extraction');
    });

    Route::resource('embedded-products', EmbeddedProductController::class);
    Route::get('/clear-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('view:cache');
        Artisan::call('config:cache');

        return '<h1>All cache cleared and optimized</h1>';
    });
    Route::post('/payments/{quoteType}/store', [CRUDController::class, 'storePayment']);
    Route::post('/payments/{quoteType}/update', [CRUDController::class, 'updatePayment']);

    Route::resource('leadassignment', LeadAssignmentController::class)->names([
        'index' => 'leadassignment.index',
        'create' => 'leadassignment.create',
        'store' => 'leadassignment.store',
        'show' => 'leadassignment.show',
        'edit' => 'leadassignment.edit',
        'update' => 'leadassignment.update',
        'destroy' => 'leadassignment.destroy',
    ]);

    Route::post('activities/v2', [ActivityController::class, 'store']);
    Route::patch('activities/v2/{id}', [ActivityController::class, 'update']);
    Route::patch('activities/v2/{id}/update-status', [ActivityController::class, 'updateStatus']);
    Route::delete('activities/v2/{id}/', [ActivityController::class, 'destroy']);

    Route::get('activities', [ActivitesController::class, 'index'])->name('activities.index');
    Route::post('/activities/create-activity', [ActivitesController::class, 'store']);
    Route::post('activities/{id}/update', [ActivitesController::class, 'update']);
    Route::post('activities/{id}/delete', [ActivitesController::class, 'destroy'])->name('activities.destroy');
    Route::post('activities/updateStatus', [ActivitesController::class, 'updateStatus'])->name('activities.updateStatus');
    Route::post('activities/getEditView', [ActivitesController::class, 'getEditView'])->name('activities.getEditView');
    Route::post('updateActivity', [CRUDController::class, 'updateActivity'])->name('updateActivity');
    Route::get('getAdvisors', [LeadAssignmentController::class, 'getAdvisors'])->name('getAdvisors');
    Route::post('get-team-managers', [UserController::class, 'getTeamManagers'])->name('getTeamManagers');
    Route::post('get-sub-teams', [UserController::class, 'getSubTeams'])->name('getSubTeams');
    Route::post('get-product-teams', [UserController::class, 'getProductTeams'])->name('getProductTeams');
    Route::get('/customer-upload', [CustomerController::class, 'uploadCustomers']);
    Route::post('/customer-process', [CustomerController::class, 'processCustomerUpload']);
    Route::post('/customer-additional-contact/{id}/delete', [CustomerController::class, 'deleteAdditionalContact']);
    Route::post('/customer-additional-contact/{id}/make-primary', [CustomerController::class, 'makeAdditionalContactPrimary']);
    Route::post('/customer-additional-contact/add', [CustomerController::class, 'addAdditionalContact']);

    Route::resource('lead-allocation', LeadAllocationController::class);
    Route::resource('car-lead-allocation', CarLeadAllocationController::class);
    Route::post('/lead-allocation/updateAvailability', [LeadAllocationController::class, 'updateAvailability']);
    Route::post('/lead-allocation/toggle-lead-allocation-job-status', [LeadAllocationController::class, 'toggleLeadAllocationJobStatus']);
    Route::post('/lead-allocation/toggle-car-lead-allocation-job-status', [LeadAllocationController::class, 'toggleCarLeadAllocationJobStatus']);
    Route::post('/lead-allocation/toggle-renewal-car-lead-allocation-status', [LeadAllocationController::class, 'toggleRenewalCarLeadAllocationStatus']);
    Route::post('/lead-allocation/toggle-car-lead-fetch-sequence', [LeadAllocationController::class, 'toggleCarLeadFetchSequence']);

    Route::get('quotes/{quoteType}/{quoteUuId}/documents', [QuoteDocumentController::class, 'list']);
    Route::post('quotes/{quoteType}/documents/store', [QuoteDocumentController::class, 'store']);
    Route::get('documents/{id}', [QuoteDocumentController::class, 'show'])->name('documents.show');
    Route::post('quotes/{quoteType}/{quoteUuId}/send-policy-documents', [QuoteDocumentController::class, 'sendPolicyDocument']);
    Route::get('quotes/{quoteType}/{quoteId}/documents/{documentTypeCode}/get-uploaded', [QuoteDocumentController::class, 'getQuoteDocumentsUploaded']);
    Route::post('documents/delete', [QuoteDocumentController::class, 'destroy']);

    Route::group(['prefix' => 'renewals'], function () {
        Route::resource('uploaded-leads', RenewalsUploadController::class);
        Route::get('uploaded-leads/{id}/validation-failed', [RenewalsUploadController::class, 'validationFailed']);
        Route::get('uploaded-leads/{id}/validation-failed/download', [RenewalsUploadController::class, 'downloadValidationFailed']);
        Route::get('uploaded-leads/{id}/validation-passed', [RenewalsUploadController::class, 'validationPassed']);
        Route::get('uploaded-leads/{id}/validation-passed/quote-redirect/{leadId}', [RenewalsUploadController::class, 'viewQuoteRedirect'])->name('viewQuoteRedirect');
        Route::get('upload', [RenewalsUploadController::class, 'uploadRenewals']);
        Route::get('batches', [RenewalsUploadController::class, 'listRenewalBatches'])->name('listRenewalBatches');
        Route::get('batches/{id}', [RenewalsUploadController::class, 'batchDetail'])->name('batchDetail');
        Route::get('batches/{id}/batch-process', [RenewalsUploadController::class, 'runBatchProcess'])->name('runBatchProcess');
        Route::post('upload-process', [RenewalsUploadController::class, 'renewalsUploadProcess']);
        Route::post('upload-create', [RenewalsUploadController::class, 'renewalsUploadCreate']);
        Route::post('upload-update', [RenewalsUploadController::class, 'renewalsUploadUpdate']);
        Route::get('batches/{id}/plans-processes', [RenewalsUploadController::class, 'plansProcesses']);
        Route::get('batches/{id}/fetch-plans', [RenewalsUploadController::class, 'fetchPlans']);
        Route::get('update', [RenewalsUploadController::class, 'updateRenewals']);
    });

    Route::post('/get-tpl-filter-stats', [DashboardController::class, 'getTPLDashboardStats']);
    Route::post('/get-comp-filter-stats', [DashboardController::class, 'getComprehensiveDashboardStats']);
    Route::post('/get-users-by-team', [DashboardController::class, 'getUsersByTeam']);
    Route::post('/get-team-conversion-stats', [DashboardController::class, 'getTeamAdvisorConversionStats']);
    Route::get('/get-recent-daily-stats', [DashboardController::class, 'getRecentDailyStats']);
    Route::get('/reports/lead-list', [ReportsController::class, 'renderLeadListReport']);
    Route::get('/dashboard/{quoteType}-conversion', [DashboardController::class, 'conversionStats']);
    Route::get('failed-jobs', [FailedJobsController::class, 'index'])->name('failed-jobs.index');

    Route::group(['prefix' => 'admin'], function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
    });

    Route::group(['prefix' => 'quotes'], function () {
        Route::resource('health', CRUDController::class);
        Route::resource('car', CRUDController::class);
        Route::get('health-cards', [HealthQuoteController::class, 'cardsView'])->name('health.cards');
        Route::get('health-export', [CRUDController::class, 'exportHealthLeads'])->name('health.export');

        Route::get('home-cards', [CRUDController::class, 'cardsViewHome']);
        Route::resource('home', CRUDController::class);
        Route::resource('business', CRUDController::class);
        if (in_array(quoteTypeCode::Business, newUi())) {
            Route::get('business/cards/view', [BusinessQuoteController::class, 'cardsView']);
            Route::resource('business', BusinessQuoteController::class);
        }
        Route::resource('travel', CRUDController::class);
        if (! in_array(quoteTypeCode::Pet, newUi())) {
            Route::resource('pet', CRUDController::class);
        }
        Route::post('save', [CRUDController::class, 'store'])->name('saveQuote');
        Route::post('update', [CRUDController::class, 'update'])->name('updateQuote');
        Route::post('createDuplicate', [CentralController::class, 'createDuplicate'])->name('createDuplicate');

        Route::get('getvalues/{modelType}/{propertyName}/{recordId}', [CRUDController::class, 'getDropdownSourceNameForDisplay']);
        Route::get('car/{quoteId}/plan_details/{planId}', [CRUDController::class, 'carQuotePlanDetails']);
        Route::post('{quoteType}/manualLeadAssign', [CRUDController::class, 'manualLeadAssign'])->name('manualLeadAssign');
        Route::post('wcuAssign', [CRUDController::class, 'wcuAssign'])->name('wcuAssign');
        Route::post('manual-tier-assignment', [CRUDController::class, 'manualTierAssignment'])->name('manualTierAssignment');
        Route::post('/{modelType}/{QuoteUId}/update-lead-status', [CRUDController::class, 'updateLeadStatus'])->name('updateLeadStatus');
        Route::post('health/healthTeamAssign', [CRUDController::class, 'healthTeamAssign'])->name('healthTeamAssign');
        Route::get('car/{quoteUuId}/updateDiscountedPremium', [CRUDController::class, 'updateDiscountedPremium']);
        Route::get('car/{quoteUuId}/create-quote', [CRUDController::class, 'addCarQuotePlan']);
        Route::get('health/{quoteId}/plan_details/{planId}', [CRUDController::class, 'health_plan_details'])->name('health_plan_detail');
        Route::post('car/SaveCarPlan', [CRUDController::class, 'SaveCarPlan'])->name('SaveCarPlan');
        Route::get('{leadId}/lead_details', [CRUDController::class, 'leadDetails'])->name('lead_details');
        Route::post('UpdateLeadManualProcess', [CRUDController::class, 'UpdateLeadManualProcess'])->name('UpdateLeadManualProcess');
        Route::post('records', [CRUDController::class, 'loadMoreRecords'])->name('loadMoreRecords');
        Route::post('records/search', [CRUDController::class, 'searchLead'])->name('searchLead');
        Route::get('getLeadHistory', [CRUDController::class, 'getLeadHistory'])->name('getLeadHistory');
        Route::post('{quoteType}/{quoteId}/car-plan-manual-process', [CRUDController::class, 'carPlanManualProcess'])->name('carPlanManualProcess');
        Route::post('car/carAssumptionsUpdate', [CRUDController::class, 'carAssumptionsUpdate']);
        Route::post('car/addNoteForCustomer', [CRUDController::class, 'addNoteForCustomer']);
        Route::post('car/sendNotesToCustomer', [CRUDController::class, 'sendNotesToCustomer']);
        Route::post('{quoteType}/update-quote-policy', [CRUDController::class, 'updateQuotePolicy']);
        Route::post('{quoteType}/manual-plan-toggle', [CRUDController::class, 'manualPlanToggle'])->name('manualPlanToggle');
        Route::post('{quoteType}/export-car-pdf', [CRUDController::class, 'exportCarPdf'])->name('exportCarPdf');
        Route::post('{quoteType}/export-health-pdf', [CRUDController::class, 'exportHealthPdf'])->name('exportHealthPdf');
        Route::post('{quoteType}/{quoteUuId}/send-email-one-click-buy', [CRUDController::class, 'sendEmailOneClickBuy'])->name('sendEmailOneClickBuy');

        if (! in_array(quoteTypeCode::Life, newUi())) {
            Route::resource('life', CRUDController::class);
        }

        if (in_array(quoteTypeCode::Travel, newUi()) || in_array(quoteTypeCode::Life, newUi())) {
            Route::get('travel-cards', [TravelController::class, 'cardsView'])->name('travel.cards');
            Route::resource('travel', TravelController::class);
            Route::get('travel/{quoteId}/plan_details/{planId}', [TravelController::class, 'planDetails'])->name('plan_details');
        } else {
            Route::get('travel/{quoteId}/plan_details/{planId}', [CRUDController::class, 'travel_plan_details'])->name('plan_details');
        }

        Route::post('car/change-insurer', [\App\Http\Controllers\V2\CarQuoteController::class, 'changeInsurer'])->name('change-car-insurer');

    });

    Route::get('personal-plans/list', [PersonalPlanController::class, 'getList']);
    Route::post('customers/{id}/additional-contacts', [\App\Http\Controllers\V2\CustomerController::class, 'storeAdditionalContact']);

    Route::group(['prefix' => 'personal-quotes'], function () {
        Route::get('{quoteId}/audit-history', [PersonalQuoteController::class, 'getAuditHistory']);
        Route::patch('{quoteId}/update-policy-details', [PersonalQuoteController::class, 'updatePolicyDetails']);
        Route::patch('{quoteType}/{quoteId}/update-status', [PersonalQuoteController::class, 'updateStatus']);
        Route::post('{quoteId}/documents', [PersonalQuoteController::class, 'uploadDocument']);
        Route::post('{quoteId}/payments', [PersonalQuoteController::class, 'createPayment']);
        Route::patch('{quoteId}/payments/{paymentCode}', [PersonalQuoteController::class, 'updatePayment']);
        Route::patch('{quoteId}/change-primary-contact', [PersonalQuoteController::class, 'changePrimaryContact']);
    });

    Route::group(['prefix' => 'generic'], function () {
        Route::resource('team', TeamController::class);
        Route::resource('tier', GenericCrudController::class);
        Route::resource('quadrant', GenericCrudController::class);
        Route::resource('rule', GenericCrudController::class);
        Route::post('save', [GenericCrudController::class, 'store'])->name('save');
        Route::post('update', [GenericCrudController::class, 'update'])->name('update');
    });

    Route::group(['prefix' => 'transapp'], function () {
        Route::resource('insurancecompany', InsuranceCompanyController::class);
        Route::resource('handler', HandlerController::class);
        Route::resource('reason', ReasonController::class);
        Route::resource('status', StatusController::class);
        Route::resource('paymentmode', PaymentModeController::class);
        Route::resource('transaction', TransactionController::class)->middleware('permission:transapp-list|transapp-create|transapp-edit|transapp-delete');
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
        // Route::resource('vehiclerange', VehicleRangeController::class);
        // Route::resource('vehiclevalue', VehicleValueController::class);
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

    Route::group(['prefix' => 'medical'], function () {
        if (in_array('Business', newUi())) {
            Route::get('amt/cards', [V2AmtController::class, 'cardsView']);
            Route::resource('amt', V2AmtController::class);
        } else {
            Route::resource('amt', AMTController::class);
        }
    });

    Route::group(['prefix' => 'discount'], function () {
        Route::resource('base', BaseDiscountController::class);
        Route::resource('age', AgeDiscountController::class);
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

    Route::get('/car-model', [AjaxController::class, 'carModelBasedOnCarMake']);
    Route::get('/car-make', [AjaxController::class, 'getCarMake']);
    Route::get('/getoverdueleads', [ClaimController::class, 'getoverdueleads']);
    Route::get('/getCarModelDetails', [AjaxController::class, 'getCarModelDetails']);
    Route::get('/getCarModelTrimValues', [AjaxController::class, 'getCarModelTrimValues']);
    Route::post('auditable', [AuditableController::class, 'loadAuditableComponent']);
    Route::post('auditlogs', [AuditableController::class, 'loadAuditLogs']);
    Route::post('audits/get-quote-audits', [AuditableController::class, 'getQuoteAudits']);
    Route::get('/car-model-by-id', [AjaxController::class, 'carModelBasedOnCarMakeId']);
    Route::post('/update-payment-status', [AjaxController::class, 'updatePaymentStatus']);
    // Route::get('/insurance-provider-plans', [ClaimController::class, 'carPlansBasedOnInsuranceProvider']); to be removed
    Route::post('/generate-payment-link', [AjaxController::class, 'generatePaymentLink']);

    Route::resource('members', MembersDetailController::class);
    Route::get('/insurance-provider-plans', [ClaimController::class, 'carPlansByInsuranceProvider']);
    Route::get('/insurance-provider-plans-health', [HealthQuoteController::class, 'plansByInsuranceProvider']);
    Route::post('/car-plan-manual-update-process', [ClaimController::class, 'carPlanUpdateManualProcess']);
    Route::resource('travelers', TravelMembersDetailController::class);
    Route::post('/health-plan-manual-update-process', [HealthQuoteController::class, 'healthPlanUpdateManualProcess']);
    Route::post('/health-plan-manual-create', [HealthQuoteController::class, 'healthPlanCreateQuote']);

    //todo: commented for later use
    //Route::get('schedule-non-motor-aml', [RenewalsUploadController::class, 'scheduleNonMotorAml']);
});

Route::POST('/sendBulkWelcomeEmails', [BulkEmailProcessController::class, 'ProcessBulkWelcomeEmails'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

/***** RestAPI */

Route::group(['middleware' => ['auth.rest']], function () {
    // ftc-form-delete schedule on 7th June 2023
    //    Route::resource('ftcform', FtcFormController::class);
    //    Route::resource('assignOE', FtcFormController::class);
    Route::group(['prefix' => 'form'], function () {
        Route::GET('/{form}', [FormController::class, 'index']);
        Route::GET('/{form}/{form_id}', [FormController::class, 'getFormDetail']);
        Route::PUT('/{form}/{form_id}', [FormController::class, 'update']);
        Route::DELETE('/{form}/{form_id}', [FormController::class, 'delete']);
        Route::POST('/{form}', [FormController::class, 'save']);
    });
    // Route::POST('/sendReviewEmail', [FormController::class,'sendReviewEmail'])
    //         ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;

    Route::group(['prefix' => 'users'], function () {
        Route::GET('/me', [UserController::class, 'me']);
    });

    Route::group(['prefix' => 'resource'], function () {
        Route::POST('/store', [UploadResourceController::class, 'store']);
    });
});

Route::POST('/processInslyRenewalData', [RenewalDataProcessingController::class, 'FetchAndProcessInslyData'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
