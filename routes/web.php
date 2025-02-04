<?php

use App\Enums\EnvEnum;
use App\Enums\PermissionsEnum;
use App\Http\Controllers\ActivitesController;
use App\Http\Controllers\AgeDiscountController;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\Allocations\LeadAllocationController as V2LeadAllocationController;
use App\Http\Controllers\AllocationThresholdController;
use App\Http\Controllers\AuditableController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BaseDiscountController;
use App\Http\Controllers\BusinessQuoteController;
use App\Http\Controllers\CarLeadAllocationController;
use App\Http\Controllers\CommercialKeywordsController;
use App\Http\Controllers\CommercialVehicleConfigurationContoller;
use App\Http\Controllers\CRUDController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\GenericCrudController;
use App\Http\Controllers\HandlerController;
use App\Http\Controllers\HealthQuoteController;
use App\Http\Controllers\InsuranceCompanyController;
use App\Http\Controllers\LeadAllocationController;
use App\Http\Controllers\LeadAssignmentController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MembersDetailController;
use App\Http\Controllers\PaymentModeController;
use App\Http\Controllers\QuoteDocumentController;
use App\Http\Controllers\QuoteExportLogController;
use App\Http\Controllers\RateCoverageUploadController;
use App\Http\Controllers\RawQueryController;
use App\Http\Controllers\ReasonController;
use App\Http\Controllers\RenewalBatchController;
use App\Http\Controllers\RenewalsUploadController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SageApi;
use App\Http\Controllers\SICConfigurableController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TmCallStatusController;
use App\Http\Controllers\TmInsuranceTypeController;
use App\Http\Controllers\TmLeadController;
use App\Http\Controllers\TmLeadStatusController;
use App\Http\Controllers\TmUploadLeadController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\TravelLeadAllocationController;
use App\Http\Controllers\TravelMembersDetailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\V2\ActivityController;
use App\Http\Controllers\V2\Admin\ProcessTrackerController;
use App\Http\Controllers\V2\Admin\QuadrantController;
use App\Http\Controllers\V2\Admin\RulesController;
use App\Http\Controllers\V2\Admin\TierController;
use App\Http\Controllers\V2\AlfredChatController;
use App\Http\Controllers\V2\AMLController;
use App\Http\Controllers\V2\AmtController as V2AmtController;
use App\Http\Controllers\V2\BikeQuoteController;
use App\Http\Controllers\V2\BuyLeadConfigController;
use App\Http\Controllers\V2\BuyLeadController;
use App\Http\Controllers\V2\CarQuoteController;
use App\Http\Controllers\V2\CarRevivalQuoteController;
use App\Http\Controllers\V2\CentralController;
use App\Http\Controllers\V2\CustomerController as V2CustomerController;
use App\Http\Controllers\V2\CycleQuoteController;
use App\Http\Controllers\V2\EmbeddedProductController;
use App\Http\Controllers\V2\FollowupController;
use App\Http\Controllers\V2\HealthRevivalQuoteController;
use App\Http\Controllers\V2\ImpersonateController;
use App\Http\Controllers\V2\JetskiQuoteController;
use App\Http\Controllers\V2\LegacyPolicyController;
use App\Http\Controllers\V2\LifeQuoteController;
use App\Http\Controllers\V2\PersonalPlanController;
use App\Http\Controllers\V2\PersonalQuoteController;
use App\Http\Controllers\V2\PetQuoteController;
use App\Http\Controllers\V2\QuoteSyncController;
use App\Http\Controllers\V2\SearchController;
use App\Http\Controllers\V2\SendUpdateLogController;
use App\Http\Controllers\V2\YachtQuoteController;
use App\Http\Controllers\ValuationController;
use App\Http\Controllers\VehicleDepreciationController;
use App\Http\Middleware\SetReadDbConnection;
use App\Services\AddBatchForNonMotors;
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

/* Auth Routes */
Route::get('login', [AuthController::class, 'login'])->name('login');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

if (config('constants.APP_ENV') != EnvEnum::PRODUCTION) {
    Route::middleware('throttle:50,10')->group(function () {
        Route::get('/alternate-login', [LoginController::class, 'index'])->name('alternate-login');
        Route::post('/alternate-login', [LoginController::class, 'login'])->name('alternate_login');
    });
}

Route::get('/get-tier-users/{tierId}', [LeadAllocationController::class, 'getTierUsers']);

Route::get('auth/google', 'App\Http\Controllers\GoogleSocialiteController@redirectToGoogle');
Route::get('google/callback', 'App\Http\Controllers\GoogleSocialiteController@handleCallback');

Route::group(['middleware' => ['auth', 'last_login_check']], function () {
    Route::get('/login-as/{id}', [ImpersonateController::class, 'loginAs'])->name('login-as.id.login');
    Route::get('/leave-login-as', [ImpersonateController::class, 'leave'])->name('login-as.leave');

    Route::get('leadsearch', function () {
        return redirect('home');
    });
    Route::get('verify-sage', [SageApi::class, 'index']);

    Route::get('home', function () {
        return inertia('Home/Home', ['im_logo' => getIMLogo()]);
    })->name('dashboard.home');

    Route::post('get-lob-raw-data', [RawQueryController::class, 'show'])->name('getRawData');

    Route::get('instant-alfred/index', [AlfredChatController::class, 'index'])->name('instant-alfred.index');

    Route::post('instant-alfred/chats', [AlfredChatController::class, 'chats']);
    Route::get('instant-alfred/export', [AlfredChatController::class, 'exportChat'])->name('exportChatData');

    Route::post('personal-quotes/{quoteType}/{code}/update-selected-plan', [CentralController::class, 'updateSelectedPlan'])->name('update-selected-plan');
    Route::post('personal-quotes/{quoteType}/{code}/save-plan-details', [CentralController::class, 'savePlanDetails'])->name('save-plan-details');
    Route::post('/reports/fetch-advisor-assigned-leads-data', [ReportsController::class, 'fetchAdvisorAssignedLeadsData'])->name('fetch-advisor-assigned-leads-data');

    Route::post('/reports/fetch-teams-by-lob', [ReportsController::class, 'fetchTeamListByLob']);
    Route::post('/reports/fetch-advisors-by-lob', [ReportsController::class, 'fetchAdvisorsListByLob']);
    Route::post('/reports/fetch-subteams-by-team', [ReportsController::class, 'fetchSubTeamListByTeam']);
    Route::post('/reports/fetch-advisor-by-team', [ReportsController::class, 'fetchAdvisorListByTeam']);
    Route::post('/reports/fetch-advisors-by-department', [ReportsController::class, 'fetchAdvisorListByDepartment']);
    Route::post('/reports/fetch-advisor-by-sub-team', [ReportsController::class, 'fetchAdvisorListBySubTeam']);
    Route::post('/reports/fetch-subteams-advisor-by-team', [ReportsController::class, 'fetchSubTeamsAdvisorListByTeam']);
    Route::get('/reports/advisor-conversion', [ReportsController::class, 'renderAdvisorConversionReport'])->name('advisor-conversion-report-view');
    Route::get('/comprehensive-conversion-dashboard', [DashboardController::class, 'renderComprehensiveDashboard'])->name('comprehensive-dashboard-view');

    Route::get('/reports/advisor-distribution', [ReportsController::class, 'renderAdvisorDistributionReport'])->name('advisor-distribution-report-view');

    Route::get('{quoteType}/report-export', [CentralController::class, 'exportLeads'])->name('retention-export');
    Route::get('/reports/retention-report', [ReportsController::class, 'renderRetentionReport'])->name('retentionn-report');
    Route::get('/reports/fetch-retention-leads-data', [ReportsController::class, 'fetchRetentionLeadsData'])->name('fetch-retention-leads-data');
    Route::post('/reports/fetch-batch-by-date', [ReportsController::class, 'fetchBatchByDates']);

    Route::group(['prefix' => 'quotes/'], function () {
        // bike routes
        Route::post('bike/bikeAssumptionsUpdate', [BikeQuoteController::class, 'bikeAssumptionsUpdate']);
        Route::post('{quoteType}/bike-manual-plan-toggle', [BikeQuoteController::class, 'manualPlanToggle'])->name('bikeManualPlanToggle');
        Route::post('quotes/{quoteType}/{quoteUuId}/bike-send-email-one-click-buy', [BikeQuoteController::class, 'sendEmailOneClickBuy'])->name('bikeSendEmailOneClickBuy');
        Route::post('{quoteType}/{quoteId}/bike-plan-manual-process', [BikeQuoteController::class, 'bikePlanManualProcess'])->name('bikePlanManualProcess');
        Route::post('/bike/change-insurer', [BikeQuoteController::class, 'changeInsurer'])->name('change-bike-insurer');
        Route::get('/get-bike-quote/{uuid}', [BikeQuoteController::class, 'getBikeQuote'])->name('getBikeQuote');
        // bike routes
    });
    Route::get('/bike-insurance-provider-plans', [BikeQuoteController::class, 'bikePlansByInsuranceProvider']);

    Route::group(['middleware' => ['check_route_access']], function () {
        Route::post('update-team-allocation-threshold', [AllocationThresholdController::class, 'updateAllocation']);
        Route::get('/accumulative-dashboard', [DashboardController::class, 'renderMainDashboard'])->name('main-dashboard-view');
        Route::get('/tpl-conversion-dashboard', [DashboardController::class, 'renderTplDashboard'])->name('tpl-dashboard-view');
        Route::get('/reports/lead-distribution', [ReportsController::class, 'renderLeadDistributionReport'])->name('lead-distribution-report-view');
        Route::get('/reports/advisor-performance', [ReportsController::class, 'renderAdvisorPerformanceReport'])->name('advisor-performance-report-view');
        Route::get('/reports/revival-conversion', [ReportsController::class, 'renderRevivalConversionReport'])->name('revival-conversion-report-view');
        Route::get('/reports/utm-report', [ReportsController::class, 'utmLeadsSaleReport'])->name('utm-leads-sales-report');
        Route::get('/reports/renewal-report', [ReportsController::class, 'renderRenewalReport'])->name('renewal-batch-report');
        Route::get('/reports/conversion-as-at', [ReportsController::class, 'renderConversionAsAtReport'])->name('conversion-as-at-report');
        Route::get('/reports/management-report', [ReportsController::class, 'renderSaleManagementReport'])->name('management-report');
        Route::get('/reports/total-premium', [ReportsController::class, 'totalPremiumLeadsSaleReport'])->name('total-premium-leads-sales-report');
        Route::get('/personal-quotes/car/car-quotes-search', [CarQuoteController::class, 'index'])->name('car-quotes-search');

        Route::get('quotes/pet/cards', [PetQuoteController::class, 'cardsView'])->name('pet-quotes-card');
        Route::resource('personal-quotes/pet', PetQuoteController::class)->names(generateRouteNames('pet-quotes'));

        Route::resource('personal-quotes/bike', BikeQuoteController::class)->names(generateRouteNames('bike-quotes'));

        Route::resource('personal-quotes/cycle', CycleQuoteController::class)->names(generateRouteNames('cycle-quotes'));
        Route::get('quotes/cycle/cards', [CycleQuoteController::class, 'cardsView'])->name('cycle-quotes-card');

        Route::resource('personal-quotes/yacht', YachtQuoteController::class)->names(generateRouteNames('yacht-quotes'));
        Route::get('quotes/yacht/cards', [YachtQuoteController::class, 'cardsView'])->name('yacht-quotes-card');

        Route::resource('personal-quotes/jetski', JetskiQuoteController::class)->names(generateRouteNames('jetski-quotes'));

        Route::group(['prefix' => 'quotes/'], function () {
            Route::get('revival', [CarRevivalQuoteController::class, 'index'])->name('carrevival-quotes-list');
            Route::get('revival/{uuid}/edit', [CarRevivalQuoteController::class, 'edit'])->name('carrevival-quotes-edit');
            Route::put('revival/{uuid}', [CarRevivalQuoteController::class, 'update'])->name('carrevival-quotes-update');

            Route::get('revival/{uuid}', [CarRevivalQuoteController::class, 'show'])->name('carrevival-quotes-show');

            // health revival
            Route::get('health-revival', [HealthRevivalQuoteController::class, 'index'])->name('health-revival-quotes-list');
            Route::get('health-revival/{uuid}', [HealthRevivalQuoteController::class, 'show'])->name('health-revival-quotes-show');
            Route::get('health-revival/{uuid}/edit', [HealthRevivalQuoteController::class, 'edit'])->name('health-revival-quotes-edit');
            Route::put('health-revival/{uuid}', [HealthRevivalQuoteController::class, 'update'])->name('health-revival-quotes-update');
        });

        Route::get('quotes/life/cards', [LifeQuoteController::class, 'cardsView'])->name('life-quotes-card');
        Route::resource('quotes/life', LifeQuoteController::class)->names(generateRouteNames('life-quotes'));

        Route::get('customer', [V2CustomerController::class, 'index'])->name('customers-list');
        Route::get('customer/{uuid}', [V2CustomerController::class, 'show'])->name('customers-show');
        Route::get('customer/{uuid}/edit', [V2CustomerController::class, 'edit'])->name('customers-edit');
        Route::put('customer/{uuid}', [V2CustomerController::class, 'update'])->name('customers-update');

        Route::get('quotes/car-sold', [CarQuoteController::class, 'getCarSoldQuotes'])->name('car-sold-list');
        Route::get('quotes/car-uncontactable', [CarQuoteController::class, 'getCarUncontactableQuotes'])->name('car-uncontactable-list');

        Route::get('{quoteType}/leads-export', [CentralController::class, 'exportLeads'])->middleware(SetReadDbConnection::class)->name('data-extraction');
        Route::get('/pua-leads-export', [CentralController::class, 'exportPUAUpdates'])->middleware(SetReadDbConnection::class)->name('export-car-pua-updates');
        Route::get('/rm-leads-export', [CentralController::class, 'exportRmLeads'])->middleware(SetReadDbConnection::class)->name('export-rm-leads');

        Route::post('save-quote-notes', [CentralController::class, 'saveQuoteNotes'])->name('save-quote-notes');
        Route::post('update-quote-notes', [CentralController::class, 'updateQuoteNotes'])->name('update-quote-notes');
        Route::delete('delete-quote-notes/{id}', [CentralController::class, 'deleteQuoteNotes'])->name('delete-quote-notes');
        Route::get('{quoteType}/leads-export-plan/{exportTye}', [CentralController::class, 'exportLeads'])->middleware(SetReadDbConnection::class)->name('export-plan-detail');
        Route::get('{quoteType}/leads-details-with-email/{exportTye}', [CentralController::class, 'exportLeads'])->middleware(SetReadDbConnection::class)->name('export-leads-detail-with-email-mobile');
        Route::get('{quoteType}/export-makes-model/{exportTye}', [CentralController::class, 'exportLeads'])->middleware(SetReadDbConnection::class)->name('export-makes-models');

        Route::group(['prefix' => 'renewals'], function () {
            Route::get('upload', [RenewalsUploadController::class, 'uploadRenewals'])->name('renewals-upload-create');
            Route::get('uploaded-leads', [RenewalsUploadController::class, 'index'])->name('renewals-uploaded-leads-list');
            Route::get('update', [RenewalsUploadController::class, 'updateRenewals'])->name('renewals-upload-update');
            Route::get('batches', [RenewalsUploadController::class, 'listRenewalBatches'])->name('renewals-batches');
            Route::get('search', [RenewalsUploadController::class, 'search'])->name('renewals-batches-search');
            Route::get('/search/export', [RenewalsUploadController::class, 'export'])->name('renewal-search-export')->middleware(SetReadDbConnection::class)->name('renewal-search-export');
        });
    });

    Route::group(['middleware' => ['permission:'.PermissionsEnum::EXTRACT_REPORT]], function () {
        Route::get('/reports/management-report/export', [ReportsController::class, 'exportManagementReport'])->name('management-report-export');
        Route::post('/reports/conversion-as-at/pdf', [ReportsController::class, 'conversionAsAtReportPdf']);
    });

    Route::group(['prefix' => 'embedded/'], function () {
        Route::get('reports', [EmbeddedProductController::class, 'reportsList'])->name('embedded-products.reports');
        Route::get('reports/{ep}', [EmbeddedProductController::class, 'reportTransactions'])->name('embedded-products.reports.certificates');
        Route::get('reports/{ep}/export', [EmbeddedProductController::class, 'reportExport'])->name('embedded-products.reports.certificates.export');
        Route::resource('products', EmbeddedProductController::class, ['names' => 'embedded-products']);
        Route::get('get-by-quote', [EmbeddedProductController::class, 'getByQuote'])->name('embedded-products.get-by-quote');
    });

    Route::resource('legacy-policy', LegacyPolicyController::class);
    Route::post('legacy-policy/move-to-imcrm', [LegacyPolicyController::class, 'moveToImcrm']);
    Route::get('legacy-policy/{policyNumber}/policy', [LegacyPolicyController::class, 'getPolicyByPolicyNumber'])->name('view-legacy-policy.renewal-uploads');
    Route::post('legacy-policy/get-s3-temp-url', [LegacyPolicyController::class, 'getS3TempUrl']);
    Route::post('migrate-legacy-policy/{poid}', [LegacyPolicyController::class, 'migratePolicy'])->name('migrate-legacy-policy');

    Route::post('embedded-products/upload-document', [EmbeddedProductController::class, 'uploadDocument'])->name('embedded-products.upload-document');
    Route::post('embedded-products/send-document', [EmbeddedProductController::class, 'sendDocument'])->name('embedded-products.send-document');
    Route::post('embedded-products/sync-document', [EmbeddedProductController::class, 'syncDocument'])->name('embedded-products.sync-document');
    Route::get('embedded-products/download/force', [EmbeddedProductController::class, 'force'])->name('force-download');

    Route::post('embedded-products/{id}/toggle-status', [EmbeddedProductController::class, 'toggleStatus'])->name('embedded-products.toggle-status');
    Route::post('embedded-products/get-documents', [EmbeddedProductController::class, 'getDocuments'])->name('embedded-products.get-documents');
    Route::post('embedded-products/upload-quote-document', [EmbeddedProductController::class, 'uploadQuoteDocument'])->name('embedded-products.upload-quote-document');

    Route::post('updateLeadStatusDragDrop', [CentralController::class, 'updateLeadStatusDragDrop'])->name('update-lead-status-drag-drop');

    Route::get('/clear-cache', function () {
        if (request()->has('info')) {
            return phpinfo();
        }
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('permission:cache-reset');
        Artisan::call('schedule:clear-cache');

        return '<h1>All cache cleared. LARAVEL Version='.app()->version().'</h1>';
    });
    Route::post('/payments/{quoteType}/store', [CRUDController::class, 'storePayment']);
    Route::post('/payments/{quoteType}/update', [CRUDController::class, 'updatePayment']);
    Route::post('/payments/{quoteType}/split-update', [CentralController::class, 'splitPaymentUpdate'])->name('approve-payments')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/split-payments-approve', [CentralController::class, 'splitPaymentsApprove'])->name('approve-payments')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/migrate-payment', [CentralController::class, 'migratePayment'])->name('payment-edit')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/update-total-price', [CentralController::class, 'updateTotalPrice'])->name('temp-update-totalprice')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/store-new', [CentralController::class, 'storeNewPayment'])->name('payment-create')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/update-new', [CentralController::class, 'updateNewPayment'])->name('payment-edit')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/retry-payment', [CentralController::class, 'retrySplitPayment'])->name('approve-payments')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/delete-split-payment', [CentralController::class, 'deleteSplitPayment'])->name('payment-edit')->middleware('check_route_access');

    Route::get('/quotes/car/post-sage-data', [SageApi::class, 'processSagePostTest'])->name('post-sage-data');

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

    Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('activities/create', [ActivityController::class, 'create'])->name('activities.create');

    Route::post('/activities/create-activity', [ActivitesController::class, 'store'])->name('activities.create.activity');
    Route::post('activities/{id}/update', [ActivitesController::class, 'update'])->name('activities.update.activity');
    Route::post('activities/{id}/delete', [ActivitesController::class, 'destroy'])->name('activities.destroy');
    Route::post('activities/updateStatus', [ActivitesController::class, 'updateStatus'])->name('activities.updateStatus');
    Route::post('activities/getEditView', [ActivitesController::class, 'getEditView'])->name('activities.getEditView');
    Route::post('updateActivity', [CRUDController::class, 'updateActivity'])->name('updateActivity');
    Route::get('getAdvisors', [LeadAssignmentController::class, 'getAdvisors'])->name('getAdvisors');
    Route::post('get-team-managers', [UserController::class, 'getTeamManagers'])->name('getTeamManagers');
    Route::post('get-sub-teams', [UserController::class, 'getSubTeams'])->name('getSubTeams');
    Route::post('get-product-teams', [UserController::class, 'getProductTeams'])->name('getProductTeams');
    Route::post('get-team-departments', [UserController::class, 'getTeamDepartments'])->name('getTeamDepartments');
    Route::get('/customer-upload', [V2CustomerController::class, 'uploadCustomers'])->name('customer.upload');
    Route::post('/customer-process', [V2CustomerController::class, 'processCustomerUpload']);
    Route::post('/customer-additional-contact/{id}/delete', [CustomerController::class, 'deleteAdditionalContact']);
    Route::post('/customer-additional-contact/{id}/make-primary', [CustomerController::class, 'makeAdditionalContactPrimary']);
    Route::post('/customer-additional-contact/add', [CustomerController::class, 'addAdditionalContact']);
    Route::post('/customer-primary-email-check', [CustomerController::class, 'customerAlreadyEmailExistCheck']);

    Route::resource('lead-allocation', LeadAllocationController::class);
    Route::resource('car-lead-allocation', CarLeadAllocationController::class);
    Route::resource('travel-lead-allocation', TravelLeadAllocationController::class);
    Route::get('allocation-dashboard/{quoteType}', [V2LeadAllocationController::class, 'index'])->name('lead-allocation-dashboard');
    Route::post('/travel-lead-allocation/update-hard-stop', [TravelLeadAllocationController::class, 'updateUserHardStopStatus']);

    Route::post('/update-cap/lead-allocation', [LeadAllocationController::class, 'updateCapsAllocation']);
    Route::get('/advisor-by-quotetype/{user_id}', [LeadAllocationController::class, 'getAdvisorByQuoteType'])->name('allocations.advisor-quotestype');

    Route::post('/lead-allocation/{quoteType}/update-availability', [LeadAllocationController::class, 'updateAvailability']);
    Route::post('/lead-allocation/{quoteType}/update-cap', [LeadAllocationController::class, 'updateCaps']);
    Route::post('/lead-allocation/toggle-reset-cap', [LeadAllocationController::class, 'updateResetCapSwitch']);
    Route::post('/lead-allocation/toggle-bl-status', [LeadAllocationController::class, 'updateBlStatus']);
    Route::post('/lead-allocation/toggle-normal-allocation', [LeadAllocationController::class, 'updateNormalLeadAllocationStatus']);
    Route::post('/lead-allocation/toggle-bl-reset-cap', [LeadAllocationController::class, 'updateBLResetCap']);
    Route::post('/lead-allocation/toggle-lead-allocation-job-status', [LeadAllocationController::class, 'toggleLeadAllocationJobStatus']);
    Route::post('/lead-allocation/toggle-car-lead-allocation-job-status', [LeadAllocationController::class, 'toggleCarLeadAllocationJobStatus']);
    Route::post('/lead-allocation/toggle-renewal-car-lead-allocation-status', [LeadAllocationController::class, 'toggleRenewalCarLeadAllocationStatus']);
    Route::post('/lead-allocation/toggle-car-lead-fetch-sequence', [LeadAllocationController::class, 'toggleCarLeadFetchSequence']);

    Route::post('quotes/documents/get-s3-temp-url', [QuoteDocumentController::class, 'getS3TempUrl']);
    Route::get('quotes/{quoteType}/{quoteUuId}/documents', [QuoteDocumentController::class, 'list']);
    Route::post('quotes/{quoteType}/{quoteUuId}/update-validate-documents', [QuoteDocumentController::class, 'validateDocumentsUpdate']);
    Route::post('quotes/{quoteType}/documents/store', [QuoteDocumentController::class, 'store']);
    Route::post('quotes/{quoteType}/documents/store-multiple', [QuoteDocumentController::class, 'storeMultiple']);
    Route::get('documents/{id}', [QuoteDocumentController::class, 'show'])->name('documents.show');
    Route::post('quotes/{quoteType}/{quoteUuId}/send-policy-documents', [QuoteDocumentController::class, 'sendPolicyDocument']);
    Route::get('quotes/{quoteType}/{quoteId}/documents/{documentTypeCode}/get-uploaded', [QuoteDocumentController::class, 'getQuoteDocumentsUploaded']);
    Route::post('documents/delete', [QuoteDocumentController::class, 'destroy']);
    Route::get('quotes/{quoteType}/{quote}/proforma-payment-request', [QuoteDocumentController::class, 'createProformaPaymentRequest'])->name('create.proforma.payment.request');
    Route::get('proforma-payment-request/{quote_document}/download', [QuoteDocumentController::class, 'downloadProformaPaymentRequest'])->name('download.proforma.payment.request');

    Route::post('documents/download', [QuoteDocumentController::class, 'downloadAllDocuments']);

    Route::group(['prefix' => 'renewals'], function () {
        Route::post('upload-create', [RenewalsUploadController::class, 'renewalsUploadCreate'])->name('upload-create');
        Route::post('upload-update', [RenewalsUploadController::class, 'renewalsUploadUpdate'])->name('upload-update');
        Route::get('batches/{id}/plans-processes', [RenewalsUploadController::class, 'plansProcesses'])->name('batch-plans-processes');
        Route::get('batches/{id}', [RenewalsUploadController::class, 'batchDetail'])->name('batch-renewal-detail');
        Route::get('batches/{id}/fetch-plans', [RenewalsUploadController::class, 'fetchPlans'])->name('batch-fetch-plans');
        Route::get('batches/{batch}/schedule-renewals-ocb', [RenewalsUploadController::class, 'scheduleRenewalsOcb'])->name('run-batch-process');
        Route::get('uploaded-leads/{id}/validation-failed', [RenewalsUploadController::class, 'validationFailed'])->name('renewal-validation-failed');
        Route::get('uploaded-leads/{id}/validation-failed/download', [RenewalsUploadController::class, 'downloadValidationFailed'])->name('validation-failed-download');
        Route::get('uploaded-leads/{id}/validation-passed', [RenewalsUploadController::class, 'validationPassed']);
        Route::get('uploaded-leads/{id}/validation-passed/quote-redirect/{leadId}', [RenewalsUploadController::class, 'viewQuoteRedirect'])->name('viewQuoteRedirect');
        Route::post('upload-process', [RenewalsUploadController::class, 'renewalsUploadProcess']);
    });

    Route::group(['prefix' => 'rates-coverages'], function () {
        Route::get('coverages', [RateCoverageUploadController::class, 'uploadCoverages'])->name('upload-coverages');
        Route::post('upload-coverages', [RateCoverageUploadController::class, 'coveragesUploadCreate'])->name('upload-coverages-create');
        Route::get('rates', [RateCoverageUploadController::class, 'uploadRates'])->name('upload-rates');
        Route::post('upload-rates', [RateCoverageUploadController::class, 'rateUploadCreate'])->name('upload-rates-create');
        Route::get('/bad-records/{id}', [RateCoverageUploadController::class, 'badRecords'])->name('bad-records');
    });

    Route::post('/get-tpl-filter-stats', [DashboardController::class, 'getTPLDashboardStats']);
    Route::post('/get-comp-filter-stats', [DashboardController::class, 'getComprehensiveDashboardStats']);
    Route::post('/get-users-by-team', [DashboardController::class, 'getUsersByTeam']);
    Route::post('/get-teams-by-product', [DashboardController::class, 'getTeamsByProduct']);

    Route::post('/get-sub-teams-by-team', [DashboardController::class, 'getSubTeamsByTeam'])->name('getSubTeamsByTeams');
    Route::post('/get-users-by-sub-team', [DashboardController::class, 'getUsersBySubTeam']);
    Route::post('/get-team-conversion-stats', [DashboardController::class, 'getTeamAdvisorConversionStats']);
    Route::get('/get-recent-daily-stats', [DashboardController::class, 'getRecentDailyStats']);
    Route::get('/reports/stale-leads', [ReportsController::class, 'renderStaleLeadsReport'])->name('stale-leads-report');
    Route::get('/reports/pipeline-report', [ReportsController::class, 'renderPipelineReport'])->name('pipeline-report');

    Route::post('/reports/fetch-advisors-by-team', [ReportsController::class, 'fetchAdvisorsByTeam'])->name('fetch-advisors-by-team');
    Route::post('/reports/fetch-teams-by-type', [ReportsController::class, 'fetchTeamsbyType'])->name('fetch-teams-by-type');

    Route::get('/reports/lead-list', [ReportsController::class, 'renderLeadListReport'])
        ->middleware('check_lead_report_access')
        ->name('lead-list-report');
    Route::get('/reports/payment-summary', [ReportsController::class, 'renderPaymentSummary'])->name('authorized-payment-summary');
    Route::get('/dashboard/{quoteType}-conversion', [DashboardController::class, 'conversionStats'])->name('dashboard.conversion.stats');

    Route::group(['prefix' => 'admin'], function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('sic-health-config', SICConfigurableController::class)->names([
            'index' => 'admin.sic-health-config.index',
            'store' => 'admin.sic-health-config.store',
        ]);
        Route::post('add-insly-advisor/{user}', [UserController::class, 'addInslyAdvisor']);
        Route::resource('departments', DepartmentController::class);
        Route::group(['prefix' => 'commerical-keywords'], function () {
            Route::get('/', [CommercialKeywordsController::class, 'index'])->name('admin.commercial.keywords');
            Route::get('/view/{commercialKeyword}', [CommercialKeywordsController::class, 'show'])->name('admin.commercial.keywords.show');
            Route::get('/create', [CommercialKeywordsController::class, 'create'])->name('admin.commercial.keywords.create');
            Route::post('/store', [CommercialKeywordsController::class, 'store'])->name('admin.commercial.keywords.store');
            Route::get('/edit/{commercialKeyword}', [CommercialKeywordsController::class, 'edit'])->name('admin.commercial.keywords.edit');
            Route::put('/update/{commercialKeyword}', [CommercialKeywordsController::class, 'update'])->name('admin.commercial.keywords.update');
        });

        Route::group(['prefix' => 'configure-commerical-vehicles'], function () {
            Route::get('/', [CommercialVehicleConfigurationContoller::class, 'index'])->name('admin.configure.commerical.vehicles');
            Route::get('/view/{carMake}', [CommercialVehicleConfigurationContoller::class, 'show'])->name('admin.configure.commerical.vehicles.show');
            Route::get('/create', [CommercialVehicleConfigurationContoller::class, 'create'])->name('admin.configure.commerical.vehicles.create');
            Route::post('/store', [CommercialVehicleConfigurationContoller::class, 'store'])->name('admin.configure.commerical.vehicles.store');
            Route::get('/edit/{carMake}', [CommercialVehicleConfigurationContoller::class, 'edit'])->name('admin.configure.commerical.vehicles.edit');
            Route::post('/update', [CommercialVehicleConfigurationContoller::class, 'update'])->name('admin.configure.commerical.vehicles.update');
        });

        Route::group(['prefix' => 'quote-sync'], function () {
            Route::middleware('readonly_db')->group(function () {
                Route::get('/', [QuoteSyncController::class, 'index'])->name('admin.quotesync');
                Route::get('/view/{quoteSync}', [QuoteSyncController::class, 'show'])->name('admin.quotesync.show');
            });
            Route::get('/edit/{quoteSync}', [QuoteSyncController::class, 'edit'])->name('admin.quotesync.edit');
            Route::put('/update/{quoteSync}', [QuoteSyncController::class, 'update'])->name('admin.quotesync.update');
            Route::post('/sync-stuck-entries', [QuoteSyncController::class, 'addStuckEntriesForSyncing'])->name('admin.quotesync.sync-stuck-entries');
            Route::post('/sync-failed-entries', [QuoteSyncController::class, 'addFailedEntriesForSyncing'])->name('admin.quotesync.sync-failed-entries');
        });

        Route::prefix('/process-tracker')->controller(ProcessTrackerController::class)->group(function () {
            Route::get('/', 'index')->name('process-tracker.index');
        });

        Route::prefix('buy-leads')->group(function () {
            Route::prefix('config')->group(function () {
                Route::get('show', [BuyLeadConfigController::class, 'show'])->name('admin.buy-leads.config.show');
                Route::post('fetch', [BuyLeadConfigController::class, 'fetch'])->name('admin.buy-leads.config.fetch');
                Route::post('upsert', [BuyLeadConfigController::class, 'upsert'])->name('admin.buy-leads.config.upsert');
            });
        });
    });

    Route::prefix('buy-leads')->group(function () {
        Route::prefix('request')->group(function () {
            Route::get('show', [BuyLeadController::class, 'show'])->name('buy-leads.request.show');
            Route::get('tracking', [BuyLeadController::class, 'tracking'])->name('buy-leads.request.tracking');
            Route::post('fetch-rate', [BuyLeadController::class, 'fetchRate'])->name('buy-leads.rate.fetch');
            Route::post('submit', [BuyLeadController::class, 'submit'])->name('buy-leads.request.submit');
        });
    });

    Route::group(['prefix' => 'quotes'], function () {
        Route::resource('health', CRUDController::class);
        Route::resource('car', CRUDController::class);
        Route::get('health-cards', [HealthQuoteController::class, 'cardsView'])->name('health.cards');

        Route::get('home-cards', [CRUDController::class, 'cardsViewHome'])->name('home-cardView');
        Route::resource('home', CRUDController::class);
        Route::resource('business', CRUDController::class);

        Route::get('business/cards/view', [BusinessQuoteController::class, 'cardsView'])->name('business.cards');
        Route::resource('business', BusinessQuoteController::class);

        Route::resource('travel', CRUDController::class);
        Route::post('save', [CRUDController::class, 'store'])->name('saveQuote');
        Route::post('update', [CRUDController::class, 'update'])->name('updateQuote');
        Route::post('cancel-payment', [EmbeddedProductController::class, 'cancelPayment'])->name('cancel-payment');
        Route::post('createDuplicate', [CentralController::class, 'createDuplicate'])->name('createDuplicate');
        Route::post('{quoteType}/leadAssign', [CentralController::class, 'manualLeadAssign'])->name('manual-lead-assignment');
        Route::post('/{quoteType}/available-plans/{id}', [CentralController::class, 'loadAvailablePlans']);

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
        Route::get('lead-history', [CRUDController::class, 'getLeadHistoryLogs'])->name('list-lead-history');
        Route::get('getLeadHistory', [CRUDController::class, 'getLeadHistory'])->name('getLeadHistory');
        Route::post('{quoteType}/{quoteId}/car-plan-manual-process', [CRUDController::class, 'carPlanManualProcess'])->name('carPlanManualProcess');
        Route::post('car/carAssumptionsUpdate', [CRUDController::class, 'carAssumptionsUpdate']);
        Route::post('car/addNoteForCustomer', [CRUDController::class, 'addNoteForCustomer']);
        Route::post('car/sendNotesToCustomer', [CRUDController::class, 'sendNotesToCustomer']);
        Route::post('{quoteType}/update-quote-policy', [CRUDController::class, 'updateQuotePolicy'])->middleware('permission:'.PermissionsEnum::POLICY_DETAILS_ADD);
        Route::post('{quoteType}/manual-plan-toggle', [CRUDController::class, 'manualPlanToggle'])->name('manualPlanToggle');
        Route::post('{quoteType}/export-car-pdf', [CRUDController::class, 'exportCarPdf'])->name('exportCarPdf');
        Route::post('{quoteType}/export-health-pdf', [CRUDController::class, 'exportHealthPdf'])->name('exportHealthPdf');
        Route::post('{quoteType}/{quoteUuId}/send-email-one-click-buy', [CRUDController::class, 'sendEmailOneClickBuy'])->name('sendEmailOneClickBuy');
        Route::post('{quoteType}/{quoteUuId}/send-email-ocb-nb', [CRUDController::class, 'sendOCBEmailNB'])->name('sendOCBEmailNB');
        Route::post('update-customer-profile', [CentralController::class, 'updateCustomerProfileDetails'])->name('update-customer-profile');
        Route::post('{quoteType}/toggle-product', [CRUDController::class, 'toggleEmbeddedProduct'])->name('toggleEmbeddedProduct');
        Route::get('{quoteType}/risk-rating-details/{quoteId}', [CRUDController::class, 'riskRatingDetails'])->name('risk-rating-details');
        Route::post('{quoteType}/{quoteUuId}/send-ocb', [CentralController::class, 'sendOCBEmail'])->name('sendOCBEmail');

        Route::get('travel-cards', [TravelController::class, 'cardsView'])->name('travel.cards');
        Route::get('travel-expired-upload', [TravelController::class, 'uploadRenewals'])->name('travel.expired.upload');
        Route::post('travel-upload-create', [TravelController::class, 'renewalsUploadCreate'])->name('travel.upload-create');
        Route::resource('travel', TravelController::class);
        Route::get('travel/{quoteId}/plan_details/{planId}', [TravelController::class, 'planDetails'])->name('plan_details');

        Route::post('car/change-insurer', [CarQuoteController::class, 'changeInsurer'])->name('change-car-insurer');

        Route::post('/export-logs/create', [QuoteExportLogController::class, 'store'])->name('export-logs.create');
    });

    Route::get('personal-plans/list', [PersonalPlanController::class, 'getList']);
    Route::post('customers/{id}/additional-contacts', [V2CustomerController::class, 'storeAdditionalContact']);

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
        Route::resource('allocation-threshold', AllocationThresholdController::class);
        Route::resource('team', TeamController::class);
        Route::resource('renewal-batches', RenewalBatchController::class)
            ->names(generateRouteNames('renewal-batches'))
            ->middleware('check_route_access');
        Route::resource('tiers', TierController::class);
        Route::resource('quadrants', QuadrantController::class);
        Route::resource('rule', RulesController::class);
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
        Route::get('/', [ValuationController::class, 'index'])->name('valuation');
        Route::post('calculate', [ValuationController::class, 'calculateValuation'])->name('valuation.calculate');
        Route::resource('vehicledepreciation', VehicleDepreciationController::class);

        Route::get('car-models', [ValuationController::class, 'carModelBasedOnCarMake'])->name('valuation.carmodels');
        Route::get('car-model-detail', [ValuationController::class, 'carTrimBasedOnCarModel'])->name('valuation.carmodeldetail');
    });

    Route::group(['prefix' => 'kyc'], function () {
        Route::resource('aml', AMLController::class);
        Route::get('aml/{quoteTypeId}/details/{quoteRequestId}', [AMLController::class, 'amlQuoteDetails']);
        Route::post('send-bridger-response', [AMLController::class, 'sendBridgerResponse'])->name('send-bridger-response');
        Route::get('aml/{quoteTypeId}/details/{quoteRequestId}/quoteUpdate', [AMLController::class, 'quoteUpdate'])->name('quoteUpdate');
        Route::get('aml-fetch-entity', [AMLController::class, 'fetchEntity'])->name('aml-fetch-entity');
        Route::get('aml/{quoteTypeId}/details/{quoteRequestId}/quoteStatusUpdate/{quoteTypeCode}', [AMLController::class, 'quoteStatusUpdate'])->name('quoteStatusUpdate');
        Route::post('aml/{quoteTypeId}/details/{quoteRequestId}/update-customer-details', [AMLController::class, 'updateCustomerDetails'])->name('aml-update-customer-details');
        Route::post('aml/{quoteTypeId}/details/{quoteRequestId}/update-entity-details', [AMLController::class, 'updateEntityDetails'])->name('aml-update-entity-details');
        Route::post('link-entity-details', [AMLController::class, 'linkEntityDetails'])->name('link-entity-details');
        Route::get('export', [AMLController::class, 'export'])->middleware(SetReadDbConnection::class);
    });
    Route::post('aml/update-quote-comment', [AMLController::class, 'updateQuoteComment'])->name('aml-update-quote-comment');

    Route::controller(SendUpdateLogController::class)->prefix('send-update')->name('send-update.')->group(function () {
        Route::post('get-options', 'getOptions')->name('get-options');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{uuid}', 'show')->name('show');
        Route::patch('/update/{id}', 'update')->name('update');
        Route::post('/save-details', 'savePriceDetails')->name('save-price-details');
        Route::post('/save-policy-details', 'savePolicyDetails')->name('save-policy-details');
        Route::post('/save-booking-details', 'saveBookingDetails')->name('save-booking-details');
        Route::post('/get-reversal-entries', 'getReversalEntries')->name('get-reversal-entries');
        Route::post('/send-update-customer-validation', 'sendUpdateCustomerValidation')->name('send-update-customer-validation');
        Route::post('/send-update-to-customer', 'sendUpdateToCustomer')->name('send-update-to-customer');
        Route::post('/save-provider-details', 'saveProviderDetails')->name('save-provider-details');
        Route::post('/book-update-validation', 'sendUpdateValidation')->name('book-update-validation');
        Route::post('book-update', 'sendUpdate')->name('book-update');
    });
    Route::get('get-plans/{quoteType}/{providerId}/{planId?}', [CentralController::class, 'getQuoteWisePlans'])->name('get-quote-wise-plans');
    // Route::get('send-update-log/{id}', [SendUpdateLogController::class, 'getLogsById'])->name('send-update.get-by-id');

    Route::group(['prefix' => 'medical'], function () {
        Route::get('amt/cards', [V2AmtController::class, 'cardsView'])->name('amt.cardsView');
        Route::resource('amt', V2AmtController::class);
    });

    Route::group(['prefix' => 'discount'], function () {
        Route::resource('base', BaseDiscountController::class);
        Route::resource('age', AgeDiscountController::class);
    });

    Route::group(['prefix' => 'telemarketing'], function () {
        Route::get('/tmleads/export', [TmLeadController::class, 'exportTMLead'])->name('tmLead.export');
        Route::resource('tmleads', TmLeadController::class)->names(generateRouteNames('tmleads'));
        Route::resource('tminsurancetype', TmInsuranceTypeController::class);
        Route::resource('tmcallstatus', TmCallStatusController::class);
        Route::resource('tmleadstatus', TmLeadStatusController::class);
        Route::get('/car-model', [TmLeadController::class, 'carModelBasedOnCarMake']);
        Route::resource('tmuploadlead', TmUploadLeadController::class)->names(generateRouteNames('tmuploadlead'));
        Route::get('tmleads/{tmLeadID}/tmLeadUpdate', [TmLeadController::class, 'tmLeadUpdate'])->name('tmLeadUpdate');
        Route::post('/tmLeadsAssign', [TmLeadController::class, 'tmLeadsAssign']);
    });

    Route::get('/car-model', [AjaxController::class, 'carModelBasedOnCarMake']);
    Route::get('/car-make', [AjaxController::class, 'getCarMake']);
    Route::get('/getCarModelDetails', [AjaxController::class, 'getCarModelDetails']);
    Route::get('/getBikeModelDetails', [AjaxController::class, 'getBikeModelDetails']);
    Route::get('/getCarModelTrimValues', [AjaxController::class, 'getCarModelTrimValues']);
    Route::get('/getBatchNamesByQuoteTypeId', [AjaxController::class, 'getBatchNamesByQuoteTypeId']);
    Route::post('auditable', [AuditableController::class, 'loadAuditableComponent']);
    Route::post('auditlogs', [AuditableController::class, 'loadAuditLogs']);
    Route::get('sage-api-logs/{sectionId}', [SageApi::class, 'sageApiLogs'])->name('sage-api-logs')->middleware('permission:'.PermissionsEnum::VIEW_SAGE_API_LOGS);
    Route::get('sage-api-logs/{sectionId}/latest-error', [SageApi::class, 'getLastSageError'])->name('sage-api-logs-latest-error');

    Route::post('insurer-logs', [AuditableController::class, 'loadApiLogs']);
    Route::post('audits/get-quote-audits', [AuditableController::class, 'getQuoteAudits']);
    Route::get('/car-model-by-id', [AjaxController::class, 'carModelBasedOnCarMakeId']);
    Route::get('/bike-model-by-id', [AjaxController::class, 'bikeModelBasedOnCarMakeId']);
    Route::get('/commercial-car-model-by-id', [AjaxController::class, 'commercialCarModelBasedOnCarMakeId']);
    Route::post('/update-payment-status', [AjaxController::class, 'updatePaymentStatus']);
    Route::post('/{quoteType}/upload-individual-kycdoc', [AjaxController::class, 'uploadKycIndividualDocument']);
    Route::post('/{quoteType}/upload-entity-kycdoc', [AjaxController::class, 'uploadKycEntityDocument']);
    Route::post('/{quoteType}/update-risk', [AjaxController::class, 'updateRisk']);
    Route::get('/{quoteType}/quote-detail/{quoteId}', [AjaxController::class, 'quoteDetail']);
    // Route::get('/insurance-provider-plans', [ClaimController::class, 'carPlansBasedOnInsuranceProvider']); to be removed
    Route::post('/generate-payment-link', [AjaxController::class, 'generatePaymentLink']);
    Route::post('update-car-plan-details', [CarQuoteController::class, 'updateCarPlanDetails']);
    Route::post('/generate-payment-link-new', [CentralController::class, 'generatePaymentLink']);

    Route::resource('members', MembersDetailController::class);
    Route::post('members/update', [MembersDetailController::class, 'uboUpdate']);
    Route::get('/insurance-provider-plans', [CarQuoteController::class, 'carPlansByInsuranceProvider']);
    Route::get('/insurance-provider-plans-health', [HealthQuoteController::class, 'plansByInsuranceProvider']);
    // health plan manual add routes
    Route::get('/network-plans-health', [HealthQuoteController::class, 'plansByNetwork']);
    Route::get('/health-plan-copays', [HealthQuoteController::class, 'copaysByPlan']);

    Route::get('/insurance-provider-networks', [HealthQuoteController::class, 'networksByInsuranceProvider']);
    // Route::post('/car-plan-manual-update-process', [ClaimController::class, 'carPlanUpdateManualProcess']);
    Route::post('/bike-plan-manual-update-process', [BikeQuoteController::class, 'bikePlanUpdateManualProcess']);
    Route::post('/car-plan-manual-update-process', [CarQuoteController::class, 'carPlanUpdateManualProcess']);
    Route::resource('travelers', TravelMembersDetailController::class);
    Route::post('/health-plan-manual-update-process', [HealthQuoteController::class, 'healthPlanUpdateManualProcess']);
    Route::post('/travel-plan-manual-update-process', [TravelController::class, 'travelPlanUpdateManualProcess']);

    // new route for health plan update process being used now

    Route::post('/health-plan-manual-update-process-v2', [HealthQuoteController::class, 'healthPlanUpdateManualProcessV2']);
    Route::post('/health-plan-manual-create', [HealthQuoteController::class, 'healthPlanCreateQuote']);
    Route::post('/health-plan-notify-agent', [HealthQuoteController::class, 'healthPlanNotifyAgent']);

    Route::post('quotes/update-last-year-policy', [CentralController::class, 'updateLastYearPolicy'])->name('update-last-year-policy');
    // bookpolicy routes
    Route::post('quotes/update-booking-policy', [CentralController::class, 'updateBookingPolicy'])->name('update-booking-policy')->middleware('permission:'.PermissionsEnum::BOOK_POLICY_DETAILS_ADD);
    Route::post('quotes/send-booking-policy', [CentralController::class, 'sendBookingPolicy'])->name('send-booking-policy')->middleware('permission:'.PermissionsEnum::SEND_POLICY_TO_CUSTOMER_BUTTON.'|'.PermissionsEnum::SEND_AND_BOOK_POLICY_BUTTON.'|'.PermissionsEnum::BOOK_POLICY_BUTTON);

    /* health quote members */
    Route::post('/health-quote-add-member', [HealthQuoteController::class, 'healthQuoteAddMember']);
    Route::put('/health-quote-update-member', [HealthQuoteController::class, 'healthQuoteUpdateMember']);
    Route::post('/health-quote-delete-member', [HealthQuoteController::class, 'healthQuoteDeleteMember']);
    Route::post('followups/emails/events', [FollowupController::class, 'getEmailEvents']);
    Route::post('/update-user-status', [UserController::class, 'updateUserStatus']);

    // Sending NB Car Followups
    Route::post('/event-followups-new-business', [CarQuoteController::class, 'sendNBEventFollowup'])->name('event-followups-new-business');

    Route::get('search-leads', [SearchController::class, 'index'])->name('search-leads');
    Route::get('search-all-export', [SearchController::class, 'searchExport'])->name('search-export');
});

Route::get('/add-batch-number', function () {
    $addBtchNuimber = new AddBatchForNonMotors;
    $addBtchNuimber->handle();
    echo 'Done';
});

// Migration Not Required For Now 21 Nov 24
// Route::get('run-insly-email-fix', function () {

//     if (\Illuminate\Support\Facades\Auth::user()?->hasRole(\App\Enums\RolesEnum::Admin)) {
//         Artisan::queue('InslyEmailFix:cron');
//     }

// });
