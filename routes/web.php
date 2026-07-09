<?php

use App\Enums\EnvEnum;
use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Http\Controllers\AccuracyMatrixController;
use App\Http\Controllers\ActivitesController;
use App\Http\Controllers\AdvisorController;
use App\Http\Controllers\AgeDiscountController;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\AllocationConfigurationController;
use App\Http\Controllers\Allocations\ClaimAllocationController;
use App\Http\Controllers\Allocations\LeadAllocationController as V2LeadAllocationController;
use App\Http\Controllers\Allocations\PqaLeadAllocationController;
use App\Http\Controllers\AllocationThresholdController;
use App\Http\Controllers\API\V1\FtcEmailLogController;
use App\Http\Controllers\AuditableController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BaseDiscountController;
use App\Http\Controllers\BranchAssignmentController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BusinessQuoteController;
use App\Http\Controllers\CarLeadAllocationController;
use App\Http\Controllers\ClaimDocumentsController;
use App\Http\Controllers\ClaimLogController;
use App\Http\Controllers\ClaimsController;
use App\Http\Controllers\CommercialKeywordsController;
use App\Http\Controllers\CommercialVehicleConfigurationContoller;
use App\Http\Controllers\CRUDController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\EAApprovalController;
use App\Http\Controllers\EALeadController;
use App\Http\Controllers\EAManagerController;
use App\Http\Controllers\GenericCrudController;
use App\Http\Controllers\HandlerController;
use App\Http\Controllers\HealthPlanTypeController;
use App\Http\Controllers\HealthQuoteController;
use App\Http\Controllers\HealthThirdPartyAdministrator;
use App\Http\Controllers\InsuranceCompanyController;
use App\Http\Controllers\LeadAllocationController;
use App\Http\Controllers\LeadAssignmentController;
use App\Http\Controllers\LifeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MembersDetailController;
use App\Http\Controllers\NationalityAllocationConfigurationController;
use App\Http\Controllers\NationalityGroupController;
use App\Http\Controllers\NationalityPoolConfigurationController;
use App\Http\Controllers\PaymentModeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\QuoteDocumentController;
use App\Http\Controllers\QuoteExportLogController;
use App\Http\Controllers\QuoteStatusLogController;
use App\Http\Controllers\RateCoverageUploadController;
use App\Http\Controllers\RawQueryController;
use App\Http\Controllers\ReasonController;
use App\Http\Controllers\RenewalBatchController;
use App\Http\Controllers\RenewalsUploadController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SageApi;
use App\Http\Controllers\SendUpdateStatusLogController;
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
use App\Http\Controllers\TravelMembersDetailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserStatusLogController;
use App\Http\Controllers\V2\ActivityController;
use App\Http\Controllers\V2\ActivityLogController;
use App\Http\Controllers\V2\Admin\AdminBuyLeadController;
use App\Http\Controllers\V2\Admin\AllocationAuditController;
use App\Http\Controllers\V2\Admin\LeadSourceController;
use App\Http\Controllers\V2\Admin\PrivateClientConfigController;
use App\Http\Controllers\V2\Admin\QuadrantController;
use App\Http\Controllers\V2\Admin\QueryBenchmarkerController;
use App\Http\Controllers\V2\Admin\RulesController;
use App\Http\Controllers\V2\Admin\SystemHealthController;
use App\Http\Controllers\V2\Admin\TierController;
use App\Http\Controllers\V2\AlfredChatController;
use App\Http\Controllers\V2\AlfredCoinsWebhookController;
use App\Http\Controllers\V2\AMLController;
use App\Http\Controllers\V2\AmtController as V2AmtController;
use App\Http\Controllers\V2\BikeQuoteController;
use App\Http\Controllers\V2\BorController;
use App\Http\Controllers\V2\BuyLeadConfigController;
use App\Http\Controllers\V2\BuyLeadController;
use App\Http\Controllers\V2\CarQuoteController;
use App\Http\Controllers\V2\CarRevivalQuoteController;
use App\Http\Controllers\V2\CentralController;
use App\Http\Controllers\V2\CustomerAcceptanceLogController;
use App\Http\Controllers\V2\CustomerController as V2CustomerController;
use App\Http\Controllers\V2\CyberQuoteController;
use App\Http\Controllers\V2\CycleQuoteController;
use App\Http\Controllers\V2\DeviceQuoteController;
use App\Http\Controllers\V2\EmbeddedProductController;
use App\Http\Controllers\V2\FollowupController;
use App\Http\Controllers\V2\HealthRevivalQuoteController;
use App\Http\Controllers\V2\HomeQuoteController;
use App\Http\Controllers\V2\HomeRevivalQuoteController;
use App\Http\Controllers\V2\ImpersonateController;
use App\Http\Controllers\V2\JetskiQuoteController;
use App\Http\Controllers\V2\LegacyPolicyController;
use App\Http\Controllers\V2\LifeRevivalQuoteController;
use App\Http\Controllers\V2\PersonalPlanController;
use App\Http\Controllers\V2\PersonalQuoteController;
use App\Http\Controllers\V2\PetQuoteController;
use App\Http\Controllers\V2\PolicyIssuanceController;
use App\Http\Controllers\V2\QuoteSyncController;
use App\Http\Controllers\V2\SageProcessesController;
use App\Http\Controllers\V2\SavingsQuoteController;
use App\Http\Controllers\V2\SearchController;
use App\Http\Controllers\V2\SendUpdateLogController;
use App\Http\Controllers\V2\YachtQuoteController;
use App\Http\Controllers\ValuationController;
use App\Http\Controllers\VehicleDepreciationController;
use App\Http\Middleware\SetReadDbConnection;
use App\Jobs\CheckHandbookDocumentsJob;
use App\Jobs\UniversalSearchDataMigration;
use App\Models\BorLog;
use App\Services\AddBatchForNonMotors;
use App\Services\Bor\BorPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

    // Expert Advisory Model
    Route::post('/ea-leads', [EALeadController::class, 'store'])->name('ea-leads.store');
    Route::post('/ea-leads/{quoteType}/{quoteId}/approve', [EAApprovalController::class, 'approve'])->name('ea-leads.approve');
    Route::post('/ea-leads/{quoteType}/{quoteId}/reject', [EAApprovalController::class, 'reject'])->name('ea-leads.reject');
    Route::get('/reports/ea-manager', [EAManagerController::class, 'index'])->name('ea-manager.index');
    Route::get('/ea-manager/export', [EAManagerController::class, 'export'])->name('ea-manager.export');
    Route::get('/ea-manager/pending-rejections', [EAManagerController::class, 'pendingRejectionsCount'])->name('ea-manager.pending-rejections');
    Route::post('/ea-manager/{quoteType}/{quoteId}/decision', [EAManagerController::class, 'decision'])->name('ea-manager.decision');
    Route::patch('/ea-manager/{quoteType}/{quoteId}/change-model', [EAManagerController::class, 'changeModel'])->name('ea-manager.change-model');

    Route::get('docs', [DocsController::class, 'show'])->name('docs.index');
    Route::get('docs/{path}', [DocsController::class, 'show'])->where('path', '.*')->name('docs.show');

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
    Route::post('instant-alfred/export-bird', [AlfredChatController::class, 'exportChatViaBird'])->name('instant-alfred.export-bird');

    Route::post('personal-quotes/{quoteType}/{code}/update-selected-plan', [CentralController::class, 'updateSelectedPlan'])->name('update-selected-plan');
    Route::post('personal-quotes/{quoteType}/{code}/save-plan-details', [CentralController::class, 'savePlanDetails'])->name('save-plan-details');
    Route::post('/reports/fetch-advisor-assigned-leads-data', [ReportsController::class, 'fetchAdvisorAssignedLeadsData'])->name('fetch-advisor-assigned-leads-data');
    Route::post('personal-quotes/{quoteType}/{code}/get-plans-payment-gateway', [CentralController::class, 'getPlansPaymentGateway'])->name('get-plans-payment-gateway');

    Route::post('/reports/fetch-teams-by-lob', [ReportsController::class, 'fetchTeamListByLob']);
    Route::post('/reports/fetch-advisors-by-lob', [ReportsController::class, 'fetchAdvisorsListByLob']);
    Route::post('/reports/fetch-subteams-by-team', [ReportsController::class, 'fetchSubTeamListByTeam']);
    Route::post('/reports/fetch-advisor-by-team', [ReportsController::class, 'fetchAdvisorListByTeam']);
    Route::post('/reports/fetch-advisors-by-department', [ReportsController::class, 'fetchAdvisorListByDepartment']);
    Route::post('/reports/fetch-advisor-by-sub-team', [ReportsController::class, 'fetchAdvisorListBySubTeam']);
    Route::post('/reports/fetch-subteams-advisor-by-team', [ReportsController::class, 'fetchSubTeamsAdvisorListByTeam']);
    Route::post('/advisors/by-quote-type', [AdvisorController::class, 'getAdvisorsByQuoteType'])->name('advisors.by-quote-type');
    Route::get('/reports/advisor-conversion', [ReportsController::class, 'renderAdvisorConversionReport'])->name('advisor-conversion-report-view');
    Route::get('/reports/conversion-optimization', [ReportsController::class, 'renderConversionOptimizationReport'])->name('conversion-optimization-report-view');
    Route::get('/comprehensive-conversion-dashboard', [DashboardController::class, 'renderComprehensiveDashboard'])->name('comprehensive-dashboard-view');

    Route::get('/reports/advisor-distribution', [ReportsController::class, 'renderAdvisorDistributionReport'])->name('advisor-distribution-report-view');

    Route::get('{quoteType}/report-export', [CentralController::class, 'exportLeads'])->middleware(SetReadDbConnection::class)->name('retention-export');
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

        // travel routes
        Route::post('travel/{quoteUuId}/send-email-one-click-buy', [TravelController::class, 'sendEmailOneClickBuy'])->name('travelSendEmailOneClickBuy');

        // cyber routes
        Route::post('cyber/{quoteUuId}/send-email-one-click-buy', [CyberQuoteController::class, 'sendEmailOneClickBuy'])->name('cyberSendEmailOneClickBuy');
    });
    Route::get('/bike-insurance-provider-plans', [BikeQuoteController::class, 'bikePlansByInsuranceProvider']);

    // home routes without check_route_access middleware
    Route::post('{quoteType}/home-manual-plan-toggle', [HomeQuoteController::class, 'manualPlanToggle'])->name('homeManualPlanToggle');
    Route::get('quotes/home/cards', [HomeQuoteController::class, 'cardsView'])->name('home-quotes-card');
    Route::get('home/{quoteId}/plan_details/{planId}', [HomeQuoteController::class, 'planDetails'])->name('home_plan_details');
    Route::post('/home-plan-manual-update-process', [HomeQuoteController::class, 'homePlanUpdateManualProcess']);

    // savings routes without check_route_access middleware
    Route::post('/savings-plan-manual-update-process', [SavingsQuoteController::class, 'savingsPlanUpdateManualProcess'])->name('savingsPlanUpdate');
    Route::get('savings/{quoteId}/plan_details/{planId}', [SavingsQuoteController::class, 'planDetails'])->name('savings_plan_details');
    Route::get('personal-quotes/savings/cards', [SavingsQuoteController::class, 'cardsView'])->name('savings-quotes-cards');
    Route::post('quotes/savings/{quoteUuId}/send-oca', [SavingsQuoteController::class, 'sendOCAEmail'])->name('savingsSendOCAEmail');
    Route::post('quotes/savings/{quoteUuId}/savings-plan-manual-process', [SavingsQuoteController::class, 'savingsPlanManualProcess'])->name('savingsPlanManualProcess');
    Route::post('quotes/savings/{quoteUuId}/fetch-savings-provider-plan', [SavingsQuoteController::class, 'fetchSavingsProviderPlan'])->name('savingsFetchProviderPlan');
    Route::get('personal-quotes/savings/provider-plans/{providerId}', [SavingsQuoteController::class, 'getProviderPlans'])->name('savings-provider-plans');
    Route::get('personal-quotes/savings/riders/{planId}', [SavingsQuoteController::class, 'riders'])->name('savings-plan-riders');
    Route::post('personal-quotes/savings/toggle-savings-plan-visibility', [SavingsQuoteController::class, 'toggleSavingsPlanVisibility'])->name('savings-plan-toggle-visibility');
    Route::post('personal-quotes/savings/update-exchange-rate', [SavingsQuoteController::class, 'updateExchangeRate'])->name('savings-plan-update-exchange-rate');
    // life routes with check_route_access middleware
    Route::get('personal-quotes/life/provider-plans/{providerId}', [LifeController::class, 'getProviderPlans'])->name('life-provider-plans');
    Route::post('personal-quotes/life/load-more-cards', [LifeController::class, 'getCardsViewLoadMore'])->name('life-quotes-load-more-cards');
    Route::get('personal-quotes/life/rider-details/{planId}', [LifeController::class, 'riderDetails'])->name('life-plan-rider-details');
    Route::get('personal-quotes/life/currency-coverages/{planId}', [LifeController::class, 'currencyCoverages'])->name('life-plan-currency-coverage');
    Route::post('personal-quotes/life/toggle-life-plan-visibility', [LifeController::class, 'toggleLifePlanVisibility'])->name('life-plan-toggle-visibility');
    Route::get('personal-quotes/life/riders/{planId}', [LifeController::class, 'riders'])->name('life-plan-riders');
    Route::post('personal-quotes/life/update-exchange-rate', [LifeController::class, 'updateExchangeRate'])->name('life-plan-update-exchange-rate');

    Route::post('personal-quotes/life/send-oca-email', [LifeController::class, 'sendOCAEmail'])->name('life-quotes-send-oca-email');
    Route::post('personal-quotes/life/download-comparision-pdf', [LifeController::class, 'downloadComparisionPdf'])->name('life-quotes-download-comparision-pdf');

    Route::post('personal-quotes/life-plan-manual-create', [LifeController::class, 'lifePlanCreateQuote']);
    Route::post('personal-quotes/life-plan-selected', [LifeController::class, 'lifePlanSelected']);
    Route::post('personal-quotes/get-life-provider-plan', [LifeController::class, 'getLifeProviderPlan']);

    Route::get('leads-by-email', [V2CustomerController::class, 'listByEmail'])->name('leads-by-email')->middleware('permission:'.PermissionsEnum::CustomersList.'|'.PermissionsEnum::LEADS_BY_EMAIL);

    Route::group(['middleware' => ['check_route_access']], function () {
        Route::post('update-team-allocation-threshold', [AllocationThresholdController::class, 'updateAllocation']);
        Route::get('/accumulative-dashboard', [DashboardController::class, 'renderMainDashboard'])->name('main-dashboard-view');
        Route::get('/tpl-conversion-dashboard', [DashboardController::class, 'renderTplDashboard'])->name('tpl-dashboard-view');
        Route::get('/reports/lead-distribution', [ReportsController::class, 'renderLeadDistributionReport'])->name('lead-distribution-report-view');
        Route::get('/reports/advisor-performance', [ReportsController::class, 'renderAdvisorPerformanceReport'])->name('advisor-performance-report-view');
        Route::get('/reports/revival-conversion', [ReportsController::class, 'renderRevivalConversionReport'])->name('revival-conversion-report-view');
        Route::get('/reports/utm-report', [ReportsController::class, 'utmLeadsSaleReport'])->name('utm-leads-sales-report');
        Route::get('/reports/utm-report/export', [ReportsController::class, 'exportUtmReport'])->name('utm-report-export');
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

        Route::get('personal-quotes/life/cards', [LifeController::class, 'cardsView'])->name('life-quotes-card');
        Route::resource('personal-quotes/life', LifeController::class)->names(generateRouteNames('life-quotes'));

        Route::resource('personal-quotes/jetski', JetskiQuoteController::class)->names(generateRouteNames('jetski-quotes'));

        Route::prefix('personal-quotes')->group(function () {
            Route::resource('/savings', SavingsQuoteController::class)->names(generateRouteNames('savings-quotes'));

            Route::resource('/smartphone', DeviceQuoteController::class)->names(generateRouteNames('device-quotes'));
        });
        Route::resource('personal-quotes/home', HomeQuoteController::class)->names(generateRouteNames('home-quotes'));

        // Accuracy Matrix API routes for IMCRM
        Route::get('accuracy-matrix/{quoteType}/{quoteId}', [AccuracyMatrixController::class, 'getMatrixStatus']);

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

        Route::get('customer', [V2CustomerController::class, 'index'])->name('customers-list');

        Route::get('customer/{uuid}', [V2CustomerController::class, 'show'])->name('customers-show');
        Route::get('customer/{uuid}/edit', [V2CustomerController::class, 'edit'])->name('customers-edit');
        Route::put('customer/{uuid}', [V2CustomerController::class, 'update'])->name('customers-update');

        Route::get('{quoteType}/leads-export', [CentralController::class, 'exportLeads'])->middleware(SetReadDbConnection::class)->name('data-extraction');
        Route::get('{quoteType}/pua-leads-export', [CentralController::class, 'exportPUAUpdates'])->middleware(SetReadDbConnection::class)->name('export-car-pua-updates');
        Route::get('/rm-leads-export', [CentralController::class, 'exportRmLeads'])->middleware(SetReadDbConnection::class)->name('export-rm-leads');

        Route::post('save-quote-notes', [CentralController::class, 'saveQuoteNotes'])->name('save-quote-notes');
        Route::post('update-quote-notes', [CentralController::class, 'updateQuoteNotes'])->name('update-quote-notes');
        Route::delete('delete-quote-notes/{id}', [CentralController::class, 'deleteQuoteNotes'])->name('delete-quote-notes');
        Route::get('{quoteType}/leads-export-plan/{exportTye}', [CentralController::class, 'exportLeads'])->middleware(SetReadDbConnection::class)->name('export-plan-detail');
        Route::get('{quoteType}/export-makes-model/{exportTye}', [CentralController::class, 'exportLeads'])->middleware(SetReadDbConnection::class)->name('export-makes-models');

        Route::group(['prefix' => 'renewals'], function () {
            Route::get('upload', [RenewalsUploadController::class, 'uploadRenewals'])->name('renewals-upload-create');
            Route::get('uploaded-leads', [RenewalsUploadController::class, 'index'])->name('renewals-uploaded-leads-list');
            Route::get('update', [RenewalsUploadController::class, 'updateRenewals'])->name('renewals-upload-update');
            Route::get('batches', [RenewalsUploadController::class, 'listRenewalBatches'])->name('renewals-batches');
            Route::get('search', [RenewalsUploadController::class, 'search'])->name('renewals-batches-search');
            Route::get('/search/export', [RenewalsUploadController::class, 'export'])->name('renewal-search-export')->middleware(SetReadDbConnection::class)->name('renewal-search-export');

            // Non Motor
            Route::get('non-motor/update', [RenewalsUploadController::class, 'updateNonMotorRenewals'])->name('non-motor-renewals-upload-update');
            Route::get('renewals/retry/{renewalsUploadLead}', [RenewalsUploadController::class, 'retryRenewalProcesses'])->name('renewals.retry');
        });

        Route::prefix('personal-quotes')->group(function () {
            Route::resource('/cyber', CyberQuoteController::class)->names(generateRouteNames('cyber-quotes'));
        });

        Route::get('personal-quotes/life-revival', [LifeRevivalQuoteController::class, 'index'])->name('life-revival-quotes-list');
        Route::get('personal-quotes/life-revival/{uuid}', [LifeRevivalQuoteController::class, 'show'])->name('life-revival-quotes-show');
        Route::get('personal-quotes/life-revival/{uuid}/edit', [LifeRevivalQuoteController::class, 'edit'])->name('life-revival-quotes-edit');
        Route::put('personal-quotes/life-revival/{uuid}', [LifeRevivalQuoteController::class, 'update'])->name('life-revival-quotes-update');

        // Routes for home revival quotes
        Route::get('personal-quotes/home-revival', [HomeRevivalQuoteController::class, 'index'])->name('home-revival-quotes-list');
        Route::get('personal-quotes/home-revival/{uuid}', [HomeRevivalQuoteController::class, 'show'])->name('home-revival-quotes-show');
        Route::get('personal-quotes/home-revival/{uuid}/edit', [HomeRevivalQuoteController::class, 'edit'])->name('home-revival-quotes-edit');
        Route::put('personal-quotes/home-revival/{uuid}', [HomeRevivalQuoteController::class, 'update'])->name('home-revival-quotes-update');
    });

    // Claims Management Routes
    Route::group(['prefix' => 'claim', 'as' => 'claims.', 'middleware' => 'claims_module_enabled'], function () {
        // Main CRUD Routes
        Route::get('/', [ClaimsController::class, 'index'])->name('index');
        Route::get('/create', [ClaimsController::class, 'create'])->name('create');
        Route::post('/', [ClaimsController::class, 'store'])->name('store');
        Route::get('/{uuid}', [ClaimsController::class, 'show'])->name('show');
        Route::get('/{uuid}/edit', [ClaimsController::class, 'edit'])->name('edit');
        Route::put('/{uuid}', [ClaimsController::class, 'update'])->name('update');

        // Search & Export
        Route::post('/search-policies', [ClaimsController::class, 'searchPolicies'])->name('search-policies');
        Route::post('/export', [ClaimsController::class, 'export'])->name('export');

        // Claim Actions
        Route::post('/bulk-assign', [ClaimsController::class, 'bulkAssignClaims'])->name('bulk-assign');
        Route::post('/{claim:uuid}/update-details', [ClaimsController::class, 'updateClaimDetails'])->name('update.details');
        Route::post('/{claim:uuid}/assign', [ClaimsController::class, 'assignClaim'])->name('assign');
        Route::post('/{claim:uuid}/update-status', [ClaimsController::class, 'updateClaimStatus'])->name('update.status');
        Route::post('/{claim:uuid}/update-complaint-status', [ClaimsController::class, 'updateComplaintStatus'])->name('update.complaint-status');
        Route::post('/{claim:uuid}/update-next-follow-up', [ClaimsController::class, 'updateNextFollowUp'])->name('update.next-follow-up');
        Route::post('/{claim:uuid}/send-notification', [ClaimsController::class, 'sendNotification'])->name('send-notification');
        Route::post('/{claim:uuid}/make-additional-contact-primary', [ClaimsController::class, 'makeAdditionalContactPrimary'])->name('make-additional-contact-primary');
        Route::post('/optimize-message', [ClaimsController::class, 'optimizeMessage'])->name('optimize-message');

        // Documents
        Route::post('/{claim:uuid}/documents', [ClaimDocumentsController::class, 'storeDocument'])->name('documents.store');
        Route::delete('/{claim:uuid}/documents/{document}', [ClaimDocumentsController::class, 'destroyDocument'])->name('documents.destroy');
        Route::post('/documents/get-s3-temp-url', [ClaimDocumentsController::class, 'getS3TempUrl'])->name('documents.get-s3-temp-url');
        Route::get('/{claim:uuid}/documents/download-all', [ClaimDocumentsController::class, 'downloadAllDocuments'])->name('documents.download-all');

        // History & Logs
        Route::get('/{claim:uuid}/lead-history', [ClaimLogController::class, 'getClaimLeadHistory'])->name('lead-history');
        Route::get('/{claim:uuid}/sub-status-logs', [ClaimLogController::class, 'getClaimSubStatusLogs'])->name('sub-status-logs');
        Route::get('/{claim:uuid}/complaint-status-logs', [ClaimLogController::class, 'getComplaintStatusLogs'])->name('complaint-status-logs');
        Route::get('/{claim:uuid}/next-follow-up-logs', [ClaimLogController::class, 'getNextFollowUpLogs'])->name('next-follow-up-logs');
    });

    // Non Motor
    Route::group(['prefix' => 'renewals'], function () {
        Route::get('non-motor/update', [RenewalsUploadController::class, 'updateNonMotorRenewals'])->name('non-motor-renewals-upload-update')->middleware('permission:'.PermissionsEnum::RENEWAL_UPLOAD_NONMOTOR);
        Route::post('non-motor/upload-update', [RenewalsUploadController::class, 'renewalsUploadUpdate'])->name('upload-update-non-motor')->middleware('permission:'.PermissionsEnum::RENEWAL_UPLOAD_NONMOTOR);
        Route::get('non-motor-batches', [RenewalsUploadController::class, 'listRenewalBatchesNonMotor'])->name('renewals-batches-nonmotor')->middleware('permission:'.PermissionsEnum::RENEWALS_BATCHES_NONMOTOR);

        // plan processes page non motor
        Route::get('batches-non-motor/{id}/{quoteType}/plans-processes', [RenewalsUploadController::class, 'plansProcessesNonMotor'])->name('batch-plans-processes.non.motor')->middleware('permission:'.PermissionsEnum::RENEWALS_BATCHES_NONMOTOR);
        Route::get('batches-non-motor/{id}/{quoteType}/fetch-plans', [RenewalsUploadController::class, 'fetchPlansNonMotor'])->name('batch-fetch-plans.non.motor')->middleware('permission:'.PermissionsEnum::RENEWALS_BATCHES_NONMOTOR);

    });

    Route::group(['middleware' => ['permission:'.PermissionsEnum::EXTRACT_REPORT]], function () {
        Route::get('/reports/management-report/export', [ReportsController::class, 'exportManagementReport'])->name('management-report-export');
        Route::get('/reports/conversion-as-at/export', [ReportsController::class, 'exportConversionAsAtReport'])->name('conversion-as-at-export');
        Route::get('/reports/conversion-optimization/export', [ReportsController::class, 'exportConversionOptimizationReport'])->name('conversion-optimization-export');
        Route::post('/reports/conversion-as-at/pdf', [ReportsController::class, 'conversionAsAtReportPdf']);
    });

    Route::group(['prefix' => 'embedded/'], function () {
        Route::get('reports', [EmbeddedProductController::class, 'reportsList'])->name('embedded-products.reports');
        Route::get('reports/{ep}', [EmbeddedProductController::class, 'reportTransactions'])->name('embedded-products.reports.certificates');
        Route::get('reports/{ep}/export', [EmbeddedProductController::class, 'reportExport'])->name('embedded-products.reports.certificates.export');
        Route::resource('products', EmbeddedProductController::class, ['names' => 'embedded-products']);
        Route::get('get-by-quote', [EmbeddedProductController::class, 'getByQuote'])->name('embedded-products.get-by-quote');
        Route::post('/courier/transaction/{code}/re-sync', [EmbeddedProductController::class, 'reSyncCourier'])->name('embedded-products.courier.re-sync');
    });

    Route::resource('legacy-policy', LegacyPolicyController::class);
    Route::post('legacy-policy/move-to-imcrm', [LegacyPolicyController::class, 'moveToImcrm']);
    Route::get('legacy-policy/{policyNumber}/policy', [LegacyPolicyController::class, 'getPolicyByPolicyNumber'])->name('view-legacy-policy.renewal-uploads');
    Route::post('legacy-policy/get-s3-temp-url', [LegacyPolicyController::class, 'getS3TempUrl']);
    Route::post('migrate-legacy-policy/{poid}', [LegacyPolicyController::class, 'migratePolicy'])->name('migrate-legacy-policy');

    Route::post('embedded-products/upload-document', [EmbeddedProductController::class, 'uploadDocument'])->name('embedded-products.upload-document');
    Route::post('embedded-products/send-document', [EmbeddedProductController::class, 'sendDocument'])->name('embedded-products.send-document');
    Route::post('embedded-products/sync-document', [EmbeddedProductController::class, 'syncDocument'])->name('embedded-products.sync-document');
    Route::post('embedded-products/sync-ep-booking', [EmbeddedProductController::class, 'syncEpBooking'])->name('embedded-products.sync-ep-booking');
    Route::post('embedded-products/reschedule-sage-booking', [EmbeddedProductController::class, 'scheduleEPSageBooking'])->name('embedded-products.reschedule-sage-booking');
    Route::get('embedded-products/download/force', [EmbeddedProductController::class, 'force'])->name('force-download');

    Route::post('embedded-products/{id}/toggle-status', [EmbeddedProductController::class, 'toggleStatus'])->name('embedded-products.toggle-status');
    Route::post('embedded-products/get-documents', [EmbeddedProductController::class, 'getDocuments'])->name('embedded-products.get-documents');
    Route::post('embedded-products/upload-quote-document', [EmbeddedProductController::class, 'uploadQuoteDocument'])->name('embedded-products.upload-quote-document');
    Route::post('embedded-products/update-ep-document', [EmbeddedProductController::class, 'updateEpDocument'])->name('embedded-products.update-ep-document');

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
    Route::post('/payment-split/post-to-sage', [CentralController::class, 'postPrepaymentToSage'])->name('can-post-premium-prepayment')->middleware('check_route_access');
    Route::post('/payment-split/retry-prepayment', [CentralController::class, 'retryPrepaymentCreation'])->name('retry-prepayment-button')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/store', [CRUDController::class, 'storePayment']);
    Route::post('/payments/{quoteType}/update', [CRUDController::class, 'updatePayment']);
    // Child payment approve
    Route::post('/payments/{quoteType}/split-payment-approve-decline', [CentralController::class, 'splitPaymentApproveDecline'])->name('approve-payments')->middleware('check_route_access');
    // Master payment approve
    Route::post('/payments/{quoteType}/master-payment-approve-capture', [CentralController::class, 'masterPaymentApproveCapture'])->name('approve-payments')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/migrate-payment', [CentralController::class, 'migratePayment'])->name('payment-edit')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/update-total-price', [CentralController::class, 'updateTotalPrice'])->name('temp-update-totalprice')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/store-new', [CentralController::class, 'storeNewPayment'])->name('payment-create')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/update-new', [CentralController::class, 'updateNewPayment'])->name('payment-edit')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/retry-payment', [CentralController::class, 'retrySplitPayment'])->name('approve-payments')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/delete-split-payment', [CentralController::class, 'deleteSplitPayment'])->name('payment-edit')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/void-payment', [CentralController::class, 'voidPayment'])->name('payments-void')->middleware('check_route_access');
    Route::post('/payments/{quoteType}/remove-insurer-payment-link', [CentralController::class, 'removeInsurerPaymentLink'])->name('payments-remove-insurer-payment-link');
    Route::post('/payments/{quoteType}/payments-capture-validation', [CentralController::class, 'paymentsCaptureValidtion'])->name('capture-validation');
    Route::post('/payments/{quoteType}/delete-payment', [CentralController::class, 'deletePayment'])->name('payments-delete');
    Route::post('/payments/{quoteType}/reset-manage-payments', [CentralController::class, 'resetManagePayments'])->name('edit-plan-after-transaction-approval')->middleware('check_route_access');

    Route::post('/payments/{quoteType}/check-insurer-receipt-number', [CentralController::class, 'checkInsurerReceiptNumber'])->name('check-insurer-receipt-number');

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
    Route::get('allocation-dashboard/{quoteType}', [V2LeadAllocationController::class, 'index'])->name('lead-allocation-dashboard');

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

    // claim allocation
    Route::middleware('claims_module_enabled')->group(function () {
        Route::get('claim-allocation-dashboard', [ClaimAllocationController::class, 'index'])->name('claim-allocation-dashboard');
        Route::post('/claim-allocation/update-availability', [ClaimAllocationController::class, 'updateAvailability'])->name('claim-allocation.update-availability');
        Route::post('/claim-allocation/update-cap', [ClaimAllocationController::class, 'updateCaps'])->name('claim-allocation.update-cap');
        Route::post('/claim-allocation/toggle-reset-cap', [ClaimAllocationController::class, 'updateResetCapSwitch']);
    });

    // Pre Qualification Advisor (PQA) lead allocation (ILA)
    Route::get('pqa-lead-allocation-dashboard', [PqaLeadAllocationController::class, 'index'])->name('pqa-lead-allocation-dashboard');
    Route::post('/pqa-allocation/update-availability', [PqaLeadAllocationController::class, 'updateAvailability'])->name('pqa-allocation.update-availability');
    Route::post('/pqa-allocation/update-cap', [PqaLeadAllocationController::class, 'updateCaps'])->name('pqa-allocation.update-cap');
    Route::post('/pqa-allocation/toggle-reset-cap', [PqaLeadAllocationController::class, 'updateResetCapSwitch'])->name('pqa-allocation.toggle-reset-cap');

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
    Route::post('documents/temp-url', [QuoteDocumentController::class, 'getTempUrl'])->name('documents.temp-url');

    Route::post('documents/download', [QuoteDocumentController::class, 'downloadAllDocuments']);

    Route::group(['prefix' => 'renewals'], function () {
        Route::post('upload-create', [RenewalsUploadController::class, 'renewalsUploadCreate'])->name('upload-create');
        Route::post('upload-update', [RenewalsUploadController::class, 'renewalsUploadUpdate'])->name('upload-update');
        Route::get('batches/{id}/plans-processes', [RenewalsUploadController::class, 'plansProcesses'])->name('batch-plans-processes');
        Route::get('batches/{id}/plans-processes-status', [RenewalsUploadController::class, 'plansProcessesStatus'])->name('batch-plans-processes-status');

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
        Route::post('update-user-state', [UserController::class, 'updateActiveState']);
        Route::get('user-status-logs', [UserStatusLogController::class, 'index'])->name('admin.user-status-logs.index');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('admin.activity-logs.index');
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);
        Route::resource('sic-health-config', SICConfigurableController::class)->names([
            'index' => 'admin.sic-health-config.index',
            'store' => 'admin.sic-health-config.store',
        ]);
        Route::post('add-insly-advisor/{user}', [UserController::class, 'addInslyAdvisor']);
        Route::resource('departments', DepartmentController::class);
        Route::resource('branches', BranchController::class);
        Route::prefix('branch-assignments')->name('branch-assignments.')->group(function () {
            Route::get('/', [BranchAssignmentController::class, 'index'])->name('index');
            Route::get('/{user}', [BranchAssignmentController::class, 'show'])->name('show');
            Route::post('/{user}', [BranchAssignmentController::class, 'store'])->name('store');
            Route::patch('/{user}/branches/{branch_id}', [BranchAssignmentController::class, 'disableAssignment'])->name('delete');
            Route::patch('/{user_id}/branches/{branch_id}/make-primary', [BranchAssignmentController::class, 'makePrimary'])->name('make-primary');
        });

        Route::get('/sync-migrate-insured-and-quote-id-to-personal-quote/{force?}', function ($force = null) {
            $forceProcess = (bool) $force;
            UniversalSearchDataMigration::dispatchSync($forceProcess, Carbon::now()->format('YmdHi'));

            return '<h3>Quote and Insured ID migration job has been dispatched. Please check the logs for detailed progress and completion status.</h3>';
        })->name('admin.sync-migrate-insured-and-quote-id-to-personal-quote');

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

        Route::prefix('buy-leads')->group(function () {
            Route::prefix('config')->group(function () {
                Route::get('show', [BuyLeadConfigController::class, 'show'])->name('admin.buy-leads.config.show');
                Route::post('fetch', [BuyLeadConfigController::class, 'fetch'])->name('admin.buy-leads.config.fetch');
                Route::post('fetch-nationalities', [BuyLeadConfigController::class, 'fetchNationalities'])->name('admin.buy-leads.config.fetch-nationalities');
                Route::post('upsert', [BuyLeadConfigController::class, 'upsert'])->name('admin.buy-leads.config.upsert');
            });
            Route::prefix('requests')->middleware('permission:'.PermissionsEnum::BUY_LEADS_ADMIN)->group(function () {
                Route::get('/', [AdminBuyLeadController::class, 'index'])->name('admin.buy-leads.requests.index');
                Route::post('/{buyLeadRequest}/expire', [AdminBuyLeadController::class, 'expire'])->name('admin.buy-leads.requests.expire');
            });
        });

        Route::prefix('private-client-config')->group(function () {
            Route::get('show', [PrivateClientConfigController::class, 'show'])->name('admin.private-client-config.show');
            Route::get('latest-by-quote-type', [PrivateClientConfigController::class, 'getLatestConfigByQuoteType'])->name('admin.private-client-config.latest-by-quote-type');
            Route::post('upsert', [PrivateClientConfigController::class, 'upsert'])->name('admin.private-client-config.upsert');
        });

        Route::prefix('benchmarker')->group(function () {
            Route::prefix('query')->group(function () {
                Route::get('show', [QueryBenchmarkerController::class, 'show'])->name('admin.benchmarker.query.show');
                Route::post('process', [QueryBenchmarkerController::class, 'process'])->name('admin.benchmarker.query.process');
            });
        });

        Route::get('/allocation-audit', [AllocationAuditController::class, 'index'])->name('admin.allocation-audit.index');

        // System Health Dashboard - Engineering role only
        Route::get('/system-health', [SystemHealthController::class, 'index'])
            ->name('admin.system-health.index');
        Route::get('/system-health/databases', [SystemHealthController::class, 'databases'])
            ->name('admin.system-health.databases');
        Route::get('/system-health/redis', [SystemHealthController::class, 'redis'])
            ->name('admin.system-health.redis');
        Route::get('/system-health/queues', [SystemHealthController::class, 'queues'])
            ->name('admin.system-health.queues');

    });

    Route::prefix('buy-leads')->group(function () {
        Route::prefix('request')->group(function () {
            Route::get('show', [BuyLeadController::class, 'show'])->name('buy-leads.request.show');
            Route::get('tracking', [BuyLeadController::class, 'tracking'])->name('buy-leads.request.tracking');
            Route::post('fetch-rate', [BuyLeadController::class, 'fetchRate'])->name('buy-leads.rate.fetch');
            Route::post('submit', [BuyLeadController::class, 'submit'])->name('buy-leads.request.submit');
            Route::get('export', [BuyLeadController::class, 'export'])->name('buy-leads.request.export');
            Route::get('export-data', [BuyLeadController::class, 'exportBuyLeadsData'])->middleware(SetReadDbConnection::class)->name('buy-leads.request.export-data');
            Route::post('update-employee-codes', [BuyLeadController::class, 'updateEmployeeCodes'])->name('buy-leads.request.update-employee-codes');
        });
    });

    Route::group(['prefix' => 'quotes'], function () {
        Route::resource('health', CRUDController::class);
        Route::resource('car', CRUDController::class);
        Route::get('health-cards', [HealthQuoteController::class, 'cardsView'])->name('health.cards');

        Route::get('home/{id}', function ($id) {
            return redirect("/personal-quotes/home/{$id}");
        })->where('id', '[A-Z0-9]+')->name('quotes.home.show');

        Route::get('home-cards', [CRUDController::class, 'cardsViewHome'])->name('home-cardView');

        Route::get('business/cards/view', [BusinessQuoteController::class, 'cardsView'])->name('business.cards');
        Route::resource('business', BusinessQuoteController::class)->middleware('check_route_access:corpline-quotes');
        Route::patch('business/{uuid}/lead-type', [BusinessQuoteController::class, 'updateLeadType'])->name('business.updateLeadType');

        Route::post('save', [CRUDController::class, 'store'])->name('saveQuote');
        Route::post('update', [CRUDController::class, 'update'])->name('updateQuote');
        Route::post('cancel-payment', [EmbeddedProductController::class, 'cancelPayment'])->name('cancel-payment');
        Route::post('void-payment', [EmbeddedProductController::class, 'voidPayment'])->name('void-payment');
        Route::post('createDuplicate', [CentralController::class, 'createDuplicate'])->name('createDuplicate');
        Route::post('{quoteType}/leadAssign', [CentralController::class, 'manualLeadAssign'])->name('manual-lead-assignment');
        Route::post('assignSupportUser', [CRUDController::class, 'assignSupportUser'])->name('assign-support-user');
        Route::post('assignPreQualificationAdvisor', [CRUDController::class, 'assignPreQualificationAdvisor'])->name('assign-pre-qualification-advisor');
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
        Route::get('car/{quoteId}/update-ocr-webform', [CarQuoteController::class, 'updateOcrWebformData'])->name('update-ocr-webform');

        Route::post('/export-logs/create', [QuoteExportLogController::class, 'store'])->name('export-logs.create');
        Route::get('status-logs', [QuoteStatusLogController::class, 'index']);
        Route::get('send-update-status-logs', [SendUpdateStatusLogController::class, 'index']);
    });

    Route::group(['prefix' => 'ftc'], function () {
        Route::get('email-logs', [FtcEmailLogController::class, 'index']);
    });

    Route::get('personal-plans/list', [PersonalPlanController::class, 'getList']);
    Route::post('customers/{id}/additional-contacts', [V2CustomerController::class, 'storeAdditionalContact']);

    Route::group(['prefix' => 'personal-quotes'], function () {
        Route::patch('{quoteId}/update-policy-details', [PersonalQuoteController::class, 'updatePolicyDetails']);
        Route::patch('{quoteType}/{quoteId}/update-status', [PersonalQuoteController::class, 'updateStatus']);
        Route::post('{quoteId}/documents', [PersonalQuoteController::class, 'uploadDocument']);
        Route::post('{quoteId}/payments', [PersonalQuoteController::class, 'createPayment']);
        Route::patch('{quoteId}/payments/{paymentCode}', [PersonalQuoteController::class, 'updatePayment']);
        Route::patch('{quoteId}/change-primary-contact', [PersonalQuoteController::class, 'changePrimaryContact']);
    });

    Route::group(['prefix' => 'generic'], function () {
        Route::resource('allocation-threshold', AllocationThresholdController::class);
        Route::get('teams-by-category/{category}', [AllocationThresholdController::class, 'getTeams'])->name('getByCategory');
        Route::resource('team', TeamController::class);
        Route::resource('renewal-batches', RenewalBatchController::class)
            ->names(generateRouteNames('renewal-batches'))
            ->middleware('check_route_access');
        Route::resource('tiers', TierController::class);
        Route::resource('quadrants', QuadrantController::class);
        Route::resource('rule', RulesController::class);
        Route::post('lead-sources', [LeadSourceController::class, 'store'])->name('lead-source.store');
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
        Route::get('home', [TransactionController::class, 'transectionHome'])->middleware('permission:transapp-search')->name('home');
        Route::get('showtransaction', [TransactionController::class, 'showTransaction'])->middleware('permission:transapp-search')->name('showtransaction');
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
        Route::get('aml', [AMLController::class, 'index'])->name('aml.index')->middleware('permission:'.PermissionsEnum::AMLList.'|'.PermissionsEnum::EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS);
        Route::resource('aml', AMLController::class)->except(['index']);
        Route::get('aml/{quoteTypeId}/details/{quoteRequestId}', [AMLController::class, 'amlQuoteDetails'])->name('amlQuoteDetails');
        Route::get('aml/{aml}/{insuredId?}/{customerId?}', [AMLController::class, 'show'])->name('aml.show');
        Route::post('send-bridger-response', [AMLController::class, 'sendBridgerResponse'])->name('send-bridger-response');
        Route::get('aml/{quoteTypeId}/details/{quoteRequestId}/quoteUpdate', [AMLController::class, 'quoteUpdate'])->name('quoteUpdate');
        Route::get('aml-fetch-entity', [AMLController::class, 'fetchEntity'])->name('aml-fetch-entity');
        Route::get('get-insured-details', [AMLController::class, 'getInsuredDetails'])->name('get-insured-details');
        Route::get('aml/{quoteTypeId}/details/{quoteRequestId}/quoteStatusUpdate/{quoteTypeCode}', [AMLController::class, 'quoteStatusUpdate'])->name('quoteStatusUpdate');
        Route::post('link-entity-details', [AMLController::class, 'linkEntityDetails'])->name('link-entity-details');
        Route::get('export', [AMLController::class, 'export'])->middleware(SetReadDbConnection::class);
        Route::post('temp-skip-bridger-aml', [AMLController::class, 'tempSkipBridgerAML'])->name('temp-skip-bridger-aml');
        Route::post('aml-ctf-report-export', [AMLController::class, 'amlCtfReportExport'])->name('aml-ctf-report-export')->middleware(SetReadDbConnection::class);
        Route::post('update-additional-vehicle-driver-details', [AMLController::class, 'updateAddtionalVehicleDriverDetails'])->name('update-additional-vehicle-driver-details');
    });
    Route::post('aml/update-quote-comment', [AMLController::class, 'updateQuoteComment'])->name('aml-update-quote-comment');

    Route::controller(SendUpdateLogController::class)->prefix('send-update')->name('send-update.')->group(function () {
        Route::post('get-options', 'getOptions')->name('get-options');
        Route::post('/store', 'store')->name('store');
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
        Route::post('/send-update-cancel', 'sendUpdateCancel')->name('send-update-cancel');
    });
    Route::get('get-plans/{quoteType}/{providerId}/{planId?}', [CentralController::class, 'getQuoteWisePlans'])->name('get-quote-wise-plans');

    Route::group(['prefix' => 'medical'], function () {
        Route::post('amt/{uuid}/ecommerce-copy-link', [V2AmtController::class, 'copyEcommerceJourneyLink'])
            ->name('amt.ecommerce-copy-link')
            ->middleware('permission:'.PermissionsEnum::GMQuoteCopyLink);
        Route::get('amt/cards', [V2AmtController::class, 'cardsView'])->name('amt.cardsView');
        Route::get('amt/networks', [V2AmtController::class, 'getNetworksByTpa'])->name('amt.networks');
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
        Route::put('tmleads/{tmLeadID}/tmLeadUpdate', [TmLeadController::class, 'tmLeadUpdate'])->name('tmLeadUpdate');
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
    Route::post('policy-issuance-logs', [AuditableController::class, 'loadPolicyIssuanceApiLogs']);
    Route::post('ocr-logs', [AuditableController::class, 'loadOcrLogs']);
    Route::post('health-routing-logs', [AuditableController::class, 'loadHealthRoutingLogs']);
    Route::post('ep-logs', [AuditableController::class, 'loadEpLogs']);
    Route::post('uae-signing-pass-logs', [AuditableController::class, 'loadUaeSigningPassLogs']);
    Route::post('audits/get-quote-audits', [AuditableController::class, 'getQuoteAudits']);
    Route::get('/car-model-by-id', [AjaxController::class, 'carModelBasedOnCarMakeId']);
    Route::get('/bike-model-by-id', [AjaxController::class, 'bikeModelBasedOnCarMakeId']);
    Route::get('/commercial-car-model-by-id', [AjaxController::class, 'commercialCarModelBasedOnCarMakeId']);
    Route::post('/update-payment-status', [AjaxController::class, 'updatePaymentStatus']);
    Route::post('update-insured-kyc', [AMLController::class, 'insuredKycDetailsUpdate'])->name('update-insured-kyc');
    Route::post('get-quote-details-from-insurer', [AMLController::class, 'getQuoteDetailsFromInsurer'])->name('get-quote-details-from-insurer');
    Route::post('toggle-policy-issuance-automation', [AMLController::class, 'togglePolicyIssuanceAutomation'])->name('toggle-policy-issuance-automation');
    Route::post('/{quoteType}/update-risk', [AjaxController::class, 'updateRisk']);
    Route::get('/{quoteType}/quote-detail/{quoteId}', [AjaxController::class, 'quoteDetail']);
    Route::post('/generate-payment-link', [AjaxController::class, 'generatePaymentLink']);
    Route::post('update-car-plan-details', [CarQuoteController::class, 'updateCarPlanDetails']);
    Route::post('/generate-payment-link-new', [CentralController::class, 'generatePaymentLink']);
    Route::post('/generate-insurer-payment-link-new', [CentralController::class, 'generateInsurerPaymentLink']);

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
    Route::group(['middleware' => ['readonly_db']], function () {
        Route::get('search-leads', [SearchController::class, 'index'])->name('search-leads');
        Route::get('search-all-export', [SearchController::class, 'searchExport'])->name('search-export');

        // Sage Failed Processes Routes
        Route::middleware(['permission:'.PermissionsEnum::SAGE_PROCESS_ISSUE_MANAGEMENT])->group(function () {
            Route::get('sage-processes/failed', [SageProcessesController::class, 'index'])->name('sage-failed-processes.index');
            Route::get('sage-processes/failed/export', [SageProcessesController::class, 'export'])->name('sage-failed-processes.export');
        });
    });

    Route::get('insurer-aml-status-logs', [CentralController::class, 'getInsurerAMLResponse'])->name('insurer-aml-status-logs');
    Route::get('check-missing-travelAml-requirement', [AMLController::class, 'checkMissingTravelAmlRequirement'])->name('check-missing-travelAml-requirement');

    Route::resource('nationality-allocation-config', NationalityAllocationConfigurationController::class)->names([
        'index' => 'admin.nationality-allocation-config.index',
        'create' => 'admin.nationality-allocation-config.create',
        'store' => 'admin.nationality-allocation-config.store',
        'show' => 'admin.nationality-allocation-config.show',
        'edit' => 'admin.nationality-allocation-config.edit',
        'update' => 'admin.nationality-allocation-config.update',
        'destroy' => 'admin.nationality-allocation-config.destroy',
    ]);

    // Nationality Pool Configuration Routes
    Route::get('nationality-pool-config', [NationalityPoolConfigurationController::class, 'index'])->name('admin.nationality-pool-config.index');
    Route::get('nationality-pool-config/data/{id?}', [NationalityPoolConfigurationController::class, 'getData'])->name('admin.nationality-pool-config.data');
    Route::post('nationality-pool-config', [NationalityPoolConfigurationController::class, 'save'])->name('admin.nationality-pool-config.save');
    Route::get('nationality-pool-audit-logs/{type}', [NationalityPoolConfigurationController::class, 'getAuditLogs'])->name('admin.nationality-pool-audit-logs');
    Route::delete('nationality-pool-audit-logs/{id}', [NationalityPoolConfigurationController::class, 'deleteAuditLog'])->name('admin.nationality-pool-audit-logs.destroy');
    Route::get('nationality-groups', [NationalityGroupController::class, 'get'])->name('admin.nationality-groups');
    Route::post('group-nationalities', [NationalityGroupController::class, 'getGroupNationalities'])->name('admin.group-nationalities');

    Route::get('nationality-allocation-config/{nationalityAllocationConfig}/audit-logs',
        [NationalityAllocationConfigurationController::class, 'getAuditLogs'])
        ->name('admin.nationality-allocation-config.audit-logs');

    // Allocation Configuration Routes
    Route::get('allocation-configuration', [AllocationConfigurationController::class, 'index'])
        ->name('admin.allocation-configuration.index');
    Route::post('allocation-configuration/fetch', [AllocationConfigurationController::class, 'fetchConfiguration'])
        ->name('admin.allocation-configuration.fetch');
    Route::post('allocation-configuration', [AllocationConfigurationController::class, 'store'])
        ->name('admin.allocation-configuration.store');
    Route::put('allocation-configuration/{allocationConfiguration}', [AllocationConfigurationController::class, 'update'])
        ->name('admin.allocation-configuration.update');
    Route::get('/teams', [AllocationConfigurationController::class, 'getTeams'])
        ->name('admin.allocation-configuration.teams');
    Route::get('/api/plan-types/{quoteType?}', [AllocationConfigurationController::class, 'getPlanTypes'])
        ->name('admin.allocation-configuration.plan-types');
    Route::get('/plan-types-by-emirates/{emirateId}', [HealthPlanTypeController::class, 'getByEmirate'])->name('planTypesByEmirates');
    Route::get('/tpa-by-insurance-provider/{insuranceProviderId}', [HealthThirdPartyAdministrator::class, 'getByInsuranceProvider'])->name('tpaByInsuranceProvider');
    Route::get('/api/business-types', [AllocationConfigurationController::class, 'getBusinessTypes'])
        ->name('admin.allocation-configuration.business-types');
    Route::get('/api/sub-areas', [AllocationConfigurationController::class, 'getSubAreas'])
        ->name('admin.allocation-configuration.sub-areas');
    Route::get('/api/departments', [AllocationConfigurationController::class, 'getDepartments'])
        ->name('admin.allocation-configuration.departments');

    Route::get('/add-batch-number', function () {
        $addBtchNuimber = new AddBatchForNonMotors;
        $addBtchNuimber->handle();
        echo 'Done';
    });

    // for testing env only.
    if (config('constants.APP_ENV') != EnvEnum::PRODUCTION) {
        Route::get('/run-advisor-payment-notification', function () {
            if (! Auth::user()?->hasRole(RolesEnum::Admin)) {
                return response()->json(['error' => 'Not authorized'], 403);
            }

            Artisan::call('send-payment-email-to-advisor:cron');

            return response()->json([
                'message' => 'Advisor payment notification command executed successfully!',
                'status' => 'completed',
            ]);
        });
    }

    // Command to bulk send policy documents
    Route::get('/run-policy-bulk-send', function (Request $request) {
        // Check if user has admin role
        if (! Auth::user()?->hasRole(RolesEnum::Admin)) {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        // Use cache lock to prevent multiple servers from executing simultaneously
        $lockKey = 'policy_bulk_send_lock';
        $lock = Cache::lock($lockKey, 600); // 10 minutes lock

        if (! $lock->get()) {
            return response()->json([
                'error' => 'Command is already running on another server. Please wait.',
                'status' => 'locked',
            ], 423); // 423 Locked
        }

        try {
            // Check if fetching from sage_process_type flag
            $fromSageProcess = $request->query('from_sage_process', false);
            $startDate = $request->query('start_date');
            $sageProcessId = $request->query('sage_process_id');

            // Build command parameters
            $params = [];
            if ($fromSageProcess) {
                $params['--from-sage-process'] = true;

                if ($startDate) {
                    $params['--start-date'] = $startDate;
                }

                if ($sageProcessId) {
                    $params['--sage-process-id'] = $sageProcessId;
                }
            }

            Artisan::call('policy:bulk-send-documents', $params);

            return response()->json([
                'message' => 'Command executed successfully!',
                'status' => 'completed',
                'from_sage_process' => (bool) $fromSageProcess,
                'start_date' => $startDate,
                'sage_process_id' => $sageProcessId,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Command execution failed: '.$e->getMessage(),
                'status' => 'failed',
            ], 500);
        } finally {
            $lock->release();
        }
    })->name('run-policy-bulk-send');

    Route::get('/check-handbook-documents/{quoteType}', function ($quoteType) {
        // Dispatch job to background queue instead of running synchronously to check the handbook documents
        CheckHandbookDocumentsJob::dispatch($quoteType, Carbon::now()->format('YmdHi'));

        return response()->json([
            'message' => "Handbook documents check for {$quoteType} has been queued for background processing",
            'status' => 'dispatched',
        ]);
    });

    // BOR (Broker on Record) Routes
    Route::group(['prefix' => 'bor'], function () {
        // BOR Request Management
        Route::get('requests/{id}/generate-link', [BorController::class, 'generateLink'])->name('bor.requests.generate-link');
        Route::resource('requests', BorController::class)->names('bor.requests')->only(['index', 'store', 'update']);
        Route::get('/get-representor', [BorController::class, 'getRepresentor'])->name('bor.get-representor');

        // BOR Status and Action Management (CRM Interface)
        Route::post('logs/{id}/cancel', [BorController::class, 'cancelBor'])->name('bor.logs.cancel');
        Route::post('logs/{id}/done', [BorController::class, 'markDone'])->name('bor.logs.mark-done');
        Route::get('logs/{id}/download', [BorController::class, 'downloadDocument'])->name('bor.logs.download-document');
        Route::get('logs/{borLogId}/signed-pdf', [BorController::class, 'viewSignedPdf'])->name('bor.logs.view-signed-pdf');

        // Document Management
        Route::post('logs/{id}/documents', [BorController::class, 'uploadDocument'])->name('bor.documents.upload');
    });

    // Customer Acceptance Logs Routes
    Route::group(['prefix' => 'consent-logs'], function () {
        Route::get('request', [CustomerAcceptanceLogController::class, 'index']);
    });

    // This route is only for testing purposes to preview the BOR PDF
    Route::get('bor-pdf-preview', function () {
        // entity bor log
        // $borLog = BorLog::where('bor_reference', 'IM-BOR-200825175545-4')->first();

        $borLog = BorLog::where('bor_reference', 'IM-BOR-210825153043-1')->first();
        $borPdfService = new BorPdfService;
        $pdfData = $borPdfService->preparePdfData($borLog, $borLog->personalQuote, true);

        return view('pdf.bor-document', $pdfData);
    });

    Route::get('trigger-policy-issuance/{policyIssuanceId}', [PolicyIssuanceController::class, 'triggerPolicyIssuance'])->middleware('permission:'.PermissionsEnum::CYBER_API_TRIGGER);
    Route::get('trigger-policy-issuance', [PolicyIssuanceController::class, 'manualTriggerPolicyIssuance'])->middleware('permission:'.PermissionsEnum::CYBER_API_TRIGGER)->name('trigger-policy-issuance');
    Route::post('re-trigger-policy-automation', [PolicyIssuanceController::class, 'reTriggerPolicyAutomation'])->middleware('permission:'.PermissionsEnum::RE_TRIGGER_POLICY_AUTOMATION_DEVICE.'|'.PermissionsEnum::RE_TRIGGER_POLICY_ISSUANCE)->name('re-trigger-policy-automation');

    Route::get('re-trigger-myalfred-coins/{list}', [AlfredCoinsWebhookController::class, 'reTrigger'])->middleware('role:ADMIN');
});
