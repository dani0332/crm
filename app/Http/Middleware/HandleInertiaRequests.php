<?php

namespace App\Http\Middleware;

use App\Enums\ActivityTypeEnum;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Enums\ClaimsEnum;
use App\Enums\CollectionTypeEnum;
use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\DocumentTypeEnum;
use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedProductTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\HealthCoverForEnum;
use App\Enums\HealthInsureEnum;
use App\Enums\HealthPolicyHolderEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\Kyc;
use App\Enums\LeadAllocationUserBLStatusFiltersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\MemberCategoryEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\PaymentAllocationStatus;
use App\Enums\PaymentCaptureValidationEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentGatewayEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\ProductionProcessTooltipEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteIssuanceStatusEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RelationCodeEnum;
use App\Enums\RolesEnum;
use App\Enums\SalaryBandEnum;
use App\Enums\SendPolicyTypeEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TravelQuoteEnum;
use App\Enums\VisaCategoryEnum;
use App\Models\BusinessTypeOfInsurance;
use App\Models\HealthPlanType;
use App\Models\PolicyIssuanceStatus;
use App\Models\User;
use App\Repositories\PaymentRepository;
use App\Services\ActivitiesService;
use App\Services\EAManagerService;
use App\Services\LeadsCountService;
use App\Services\OCR\OCRService;
use App\Services\SplitPaymentService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Spatie\Navigation\Navigation;
use Spatie\Navigation\Section;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'layouts/app_inertia';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(Request $request): array
    {
        $permissions = $roles = [];
        $vatValue = 0;
        if (auth()->user()) {
            $permissions = auth()->user()->getAllPermissions()->pluck('name')->toArray();
            $roles = auth()->user()->getRoleNames()->toArray();
            $vatValue = getAppStorageValueByKey(ApplicationStorageEnums::VAT_VALUE, useCache: true);
        }

        $authID = Auth::id() ?? 0;

        return [
            ...parent::share($request),
            'auth.user' => fn () => $request->user()
                ? $request->user()->only('id', 'name', 'email', 'profile_photo_path', 'status', 'can_impersonate')
                : null,
            'auth.permissions' => fn () => $permissions,
            'auth.roles' => fn () => $roles,
            'auth.teams' => fn () => $request->user()
                ? $request->user()->teams()->get()->select('id', 'name')
                : [],
            'sidebar' => fn () => $this->buildNavigation()->tree(),
            'location' => fn () => $request->url(),
            'permissionsEnum' => PermissionsEnum::asArray(),
            'rolesEnum' => RolesEnum::asArray(),
            'teamNamesEnum' => TeamNameEnum::asArray(),
            'insuranceProviderCodeEnum' => InsuranceProviderEnum::asArray(),
            'paymentStatusEnum' => PaymentStatusEnum::asArray(),
            'documentTypeEnum' => DocumentTypeEnum::asArray(),
            'sendPolicyTypeEnum' => SendPolicyTypeEnum::asArray(),
            'quoteTypeIds' => QuoteTypeId::asArray(),
            'quoteTypeCodeEnum' => quoteTypeCode::asArray(),
            'travelQuoteEnum' => TravelQuoteEnum::asArray(),
            'quoteIssuanceStatusEnum' => QuoteIssuanceStatusEnum::asArray(),
            'quoteBusinessTypeCode' => quoteBusinessTypeCode::asArray(),
            'quoteBusinessTypeIdEnum' => BusinessTypeOfInsuranceIdEnum::asArray(),
            'paymentCaptureValidationEnum' => PaymentCaptureValidationEnum::asArray(),
            'leadSource' => LeadSourceEnum::asArray(),
            'flash' => fn () => $this->shareFlashData($request),
            'baseUrl' => url('/'),
            'appEnv' => config('constants.APP_ENV'),
            'pusherKey' => config('constants.VITE_PUSHER_APP_KEY'),
            'pusherCluster' => config('constants.VITE_PUSHER_APP_CLUSTER'),
            'epLink' => config('constants.AFIA_WEBSITE_DOMAIN'),
            'ecomBaseUrl' => config('constants.ECOM_BASE_URL'),
            'vat' => ApplicationStorageEnums::VAT,
            'paymentMethodsEnum' => PaymentMethodsEnum::asArray(),
            'sendUpdateLogStatusEnum' => SendUpdateLogStatusEnum::asArray(),
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
            'amlStatusEnum' => AMLStatusCode::asArray(),
            'totalQuotesCount' => LeadsCountService::getLeadCount(),
            'im_logo' => getIMLogo(),
            'authorisePaymentCount' => fn () => app(PaymentRepository::class)->getAuthorisePaymentCount(),
            'eaPendingRejectionsCount' => fn () => auth()->user()?->hasRole(RolesEnum::EAManager)
                ? app(EAManagerService::class)->pendingRejectionsCount()
                : 0,
            'checkAuthUserRole' => checkAuthUserRole(),
            'quoteSegments' => QuoteSegmentEnum::withLabels(),
            'paymentLookups' => Cache::remember('shared_payment_lookups', now()->addHour(), fn () => app(SplitPaymentService::class)->getPaymentLookups()),
            'vatValue' => $vatValue,
            'productionProcessTooltipEnum' => ProductionProcessTooltipEnum::asArray(),
            'policyIssuanceStatus' => Cache::remember('policy_issuance_statuses', now()->addHour(), fn () => PolicyIssuanceStatus::active()->get()),
            'policyIssuanceStatusEnum' => PolicyIssuanceStatusEnum::asArray(),
            'policyIssuanceEnum' => PolicyIssuanceEnum::asArray(),
            'paymentAllocationStatus' => PaymentAllocationStatus::asArray(),
            'lookupsEnum' => getLookupsEnum(),
            'kycEnums' => Kyc::asArray(),
            'documentTypeCodeEnum' => DocumentTypeCode::asArray(),
            'paymentFrequencyEnum' => PaymentFrequency::asArray(),
            'pendingActivityCount' => app(ActivitiesService::class)->getPendingActivityCount(),
            'quoteTypes' => QuoteTypes::allTypesWithIds(),
            'healthPlanTypes' => Cache::remember('health_plan_types', now()->addHour(), fn () => HealthPlanType::where('is_active', 1)->select('id', 'text')->orderBy('id')->get()),
            'businessTypeOfInsurances' => Cache::remember('business_type_of_insurances', now()->addHour(), fn () => BusinessTypeOfInsurance::active()->select('id', 'text')->get()),
            'claimsEnum' => ClaimsEnum::asArray(),
            'embeddedProductEnum' => EmbeddedProductEnum::asArray(),
            'embeddedProductTypeEnum' => EmbeddedProductTypeEnum::asArray(),
            'activityTypeEnum' => ActivityTypeEnum::asArray(),
            'carRegistrationType' => CarRegistrationType::asArray(),
            'carVehicleUse' => CarVehicleUse::asArray(),
            'isTapEnabled' => isTapEnabled(),
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'impersonatingUser' => app('impersonate')?->getImpersonatorId() ? User::find(app('impersonate')?->getImpersonatorId()) : null,
            'paymentGatewayEnum' => PaymentGatewayEnum::asArray(),
            'carVehicleUse' => CarVehicleUse::asArray(),
            'ocrDocumentTypeEnum' => OCRDocumentTypeEnum::asArray(),
            'eligibleOcrProviders' => app(OCRService::class)->getEligibleProviders(),
            'genericRequestEnum' => GenericRequestEnum::asArray(),
            'collectionTypeEnum' => CollectionTypeEnum::asArray(),
            'cdnPath' => config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/',
            'healthInsureEnum' => array_column(HealthInsureEnum::cases(), 'value', 'name'),
            'healthPolicyHolderEnum' => array_column(HealthPolicyHolderEnum::cases(), 'value', 'name'),
            'healthCoverForEnum' => array_column(HealthCoverForEnum::cases(), 'value', 'name'),
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'memberCategoryEnum' => array_column(MemberCategoryEnum::cases(), 'value', 'name'),
            'relationCodeEnum' => array_column(RelationCodeEnum::cases(), 'value', 'name'),
            'salaryBandEnum' => array_column(SalaryBandEnum::cases(), 'value', 'name'),
            'visaCategoryEnum' => array_column(VisaCategoryEnum::cases(), 'value', 'name'),
        ];
    }

    protected function shareFlashData(Request $request)
    {
        $flash = [
            'message' => $request->session()->get('message'),
            'error' => $request->session()->get('error'),
            'success' => $request->session()->get('success'),
            'warning' => $request->session()->get('warning'),
            'info' => $request->session()->get('info'),
        ];

        return array_filter($flash, fn ($value) => $value !== null);
    }

    protected function buildNavigation()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $nav = app(Navigation::class)
            ->add('Home', route('dashboard.home'));

        if (auth()->user()->hasAnyPermission(array_merge([
            PermissionsEnum::DashboardView,
            PermissionsEnum::TPL_DASHBOARD_VIEW,
            PermissionsEnum::MAIN_DASHBOARD_VIEW,
            PermissionsEnum::UtmLeadsSalesReport,
        ], PermissionsEnum::getComprehensiveDashboardPermissions()))) {
            $nav = $nav->add('Dashboard', '', function (Section $section) {
                $section
                    ->add('Car Conversion', route('dashboard.conversion.stats', ['quoteType' => 'car']), fn ($s) => $s->attributes(['icon' => 'car']))
                    ->add('Travel Conversion', route('dashboard.conversion.stats', ['quoteType' => 'travel']), fn ($s) => $s->attributes(['icon' => 'travel']))
                    ->addIf(
                        auth()->user()->hasAnyPermission(PermissionsEnum::getComprehensiveDashboardPermissions()),
                        'Comprehensive Conversion',
                        route('comprehensive-dashboard-view'),
                        fn ($s) => $s->attributes(['icon' => 'graph'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([PermissionsEnum::MAIN_DASHBOARD_VIEW, PermissionsEnum::VIEW_ALL_LEADS]),
                        'Accumulative Dashboard',
                        route('main-dashboard-view'),
                        fn ($s) => $s->attributes(['icon' => 'graph'])
                    );
            });
        }

        if (auth()->user()->hasAnyPermission(array_merge(
            [
                PermissionsEnum::ADVISOR_PERFORMANCE_REPORT_VIEW,
                PermissionsEnum::ADVISOR_DISTRIBUTION_REPORT_VIEW,
                PermissionsEnum::LEAD_DISTRIBUTION_REPORT_VIEW,
                PermissionsEnum::REVIVAL_CONVERSION_REPORT_VIEW,
                PermissionsEnum::UtmLeadsSalesReport,
                PermissionsEnum::RENEWAL_BATCH_REPORT,
                PermissionsEnum::CONVERSION_AS_AT_REPORT,
                PermissionsEnum::CONVERSION_OPTIMIZATION_ENGINE_REPORT_VIEW,
                PermissionsEnum::MANAGEMENT_REPORT,
                PermissionsEnum::VIEW_ALL_REPORTS,
            ],
            PermissionsEnum::getAdvisorConversionReportPermissions(),
            PermissionsEnum::getAdvisorDistributionReportPermissions()
        ))) {
            $nav = $nav->add('Reports', '', function (Section $section) {
                $section
                    ->addIf(auth()->user()->can(PermissionsEnum::CONVERSION_AS_AT_REPORT), 'Conversion As At Report', route('conversion-as-at-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->hasAnyPermission(array_merge(PermissionsEnum::getAdvisorConversionReportPermissions(), [PermissionsEnum::VIEW_ALL_REPORTS])), 'Advisor Conversion', route('advisor-conversion-report-view'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->hasAnyPermission([PermissionsEnum::CONVERSION_OPTIMIZATION_ENGINE_REPORT_VIEW, PermissionsEnum::VIEW_ALL_REPORTS]), 'Conversion Optimization Engine', route('conversion-optimization-report-view'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->hasAnyPermission([PermissionsEnum::ADVISOR_PERFORMANCE_REPORT_VIEW, PermissionsEnum::VIEW_ALL_REPORTS]), 'Advisor Performance', route('advisor-performance-report-view'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->hasAnyPermission(array_merge(PermissionsEnum::getAdvisorDistributionReportPermissions(), [PermissionsEnum::VIEW_ALL_REPORTS])), 'Advisor Distribution', route('advisor-distribution-report-view'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->hasAnyPermission([PermissionsEnum::LEAD_DISTRIBUTION_REPORT_VIEW, PermissionsEnum::VIEW_ALL_REPORTS]), 'Lead Distribution', route('lead-distribution-report-view'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::REVIVAL_CONVERSION_REPORT_VIEW), 'Revival Conversion', route('revival-conversion-report-view'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::UtmLeadsSalesReport), 'UTM Report', route('utm-leads-sales-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::RENEWAL_BATCH_REPORT), 'Motor Retention report', route('renewal-batch-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->hasAnyPermission([PermissionsEnum::MANAGER_AUTHORISED_PAYMENT_SUMMARY, PermissionsEnum::VIEW_ALL_REPORTS]), 'Authorised Payment Summary', route('authorized-payment-summary', [], false), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::MANAGEMENT_REPORT), 'Management Report', route('management-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(app(UserService::class)->isAllowedToShowLeadListReport(), 'Lead List Report', route('lead-list-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::STALE_LEADS_REPORT), 'Stale Leads Report', route('stale-leads-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::PIPELINE_REPORT), 'Pipeline Report', route('pipeline-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf((auth()->user()->can(PermissionsEnum::TOTAL_PREMIUM_LEADS_SALES_REPORT) || (auth()->user()->can(PermissionsEnum::VIEW_ALL_REPORTS) && userHasProduct(quoteTypeCode::Car))), 'Total Premium Report', route('total-premium-leads-sales-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(true, 'Non Motor Retention Report', route('retentionn-report'), fn ($s) => $s->attributes(['icon' => 'bar']));
            });
        }

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::CAR_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::CAR_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::HEALTH_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::HEALTH_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::HEALTH_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::TRAVEL_SIC_ALLOCATION,
            PermissionsEnum::TRAVEL_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::TRAVEL_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::TRAVEL_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::LIFE_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::LIFE_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::LIFE_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::HOME_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::HOME_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::HOME_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::PET_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::PET_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::PET_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::CORPLINE_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::CORPLINE_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::CORPLINE_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::CYCLE_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::CYCLE_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::CYCLE_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::YACHT_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::YACHT_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::YACHT_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::UtmLeadsSalesReport,
            PermissionsEnum::CYBER_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::CYBER_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::CYBER_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::DEVICE_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::DEVICE_LEAD_ALLOCATION_EDIT,
        ])) {
            $nav = $nav->add('Lead Allocation', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::HEALTH_LEAD_ALLOCATION_DASHBOARD,
                            PermissionsEnum::HEALTH_LEAD_ALLOCATION_VIEW_ONLY,
                            PermissionsEnum::HEALTH_LEAD_ALLOCATION_EDIT,
                        ]),
                        'Health',
                        route('lead-allocation.index', ['userBlStatus' => LeadAllocationUserBLStatusFiltersEnum::BUY_LEAD_DISABLED->value]),
                        fn ($s) => $s->attributes(['icon' => 'health'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::CAR_LEAD_ALLOCATION_DASHBOARD,
                            PermissionsEnum::CAR_LEAD_ALLOCATION_VIEW_ONLY,
                            PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT,
                        ]),
                        'Car',
                        route('car-lead-allocation.index', ['userBlStatus' => LeadAllocationUserBLStatusFiltersEnum::BUY_LEAD_DISABLED->value]),
                        fn ($s) => $s->attributes(['icon' => 'car'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::TRAVEL_SIC_ALLOCATION,
                            PermissionsEnum::TRAVEL_LEAD_ALLOCATION_DASHBOARD,
                            PermissionsEnum::TRAVEL_LEAD_ALLOCATION_VIEW_ONLY,
                            PermissionsEnum::TRAVEL_LEAD_ALLOCATION_EDIT,
                        ]),
                        'Travel',
                        route('lead-allocation-dashboard', ['quoteType' => QuoteTypes::TRAVEL]),
                        fn ($s) => $s->attributes(['icon' => 'travel'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::LIFE_LEAD_ALLOCATION_DASHBOARD,
                            PermissionsEnum::LIFE_LEAD_ALLOCATION_VIEW_ONLY,
                            PermissionsEnum::LIFE_LEAD_ALLOCATION_EDIT,
                        ]),
                        'Life',
                        route('lead-allocation-dashboard', ['quoteType' => QuoteTypes::LIFE]),
                        fn ($s) => $s->attributes(['icon' => 'life'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::HOME_LEAD_ALLOCATION_DASHBOARD,
                            PermissionsEnum::HOME_LEAD_ALLOCATION_VIEW_ONLY,
                            PermissionsEnum::HOME_LEAD_ALLOCATION_EDIT,
                        ]),
                        'Home',
                        route('lead-allocation-dashboard', ['quoteType' => QuoteTypes::HOME]),
                        fn ($s) => $s->attributes(['icon' => 'home'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::PET_LEAD_ALLOCATION_DASHBOARD,
                            PermissionsEnum::PET_LEAD_ALLOCATION_VIEW_ONLY,
                            PermissionsEnum::PET_LEAD_ALLOCATION_EDIT,
                        ]),
                        'Pet',
                        route('lead-allocation-dashboard', ['quoteType' => QuoteTypes::PET]),
                        fn ($s) => $s->attributes(['icon' => 'pet'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::CORPLINE_LEAD_ALLOCATION_DASHBOARD,
                            PermissionsEnum::CORPLINE_LEAD_ALLOCATION_VIEW_ONLY,
                            PermissionsEnum::CORPLINE_LEAD_ALLOCATION_EDIT,
                        ]),
                        'Corpline',
                        route('lead-allocation-dashboard', ['quoteType' => QuoteTypes::CORPLINE]),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::CYCLE_LEAD_ALLOCATION_DASHBOARD,
                            PermissionsEnum::CYCLE_LEAD_ALLOCATION_VIEW_ONLY,
                            PermissionsEnum::CYCLE_LEAD_ALLOCATION_EDIT,
                        ]),
                        'Cycle',
                        route('lead-allocation-dashboard', ['quoteType' => QuoteTypes::CYCLE]),
                        fn ($s) => $s->attributes(['icon' => 'cycle'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::YACHT_LEAD_ALLOCATION_DASHBOARD,
                            PermissionsEnum::YACHT_LEAD_ALLOCATION_VIEW_ONLY,
                            PermissionsEnum::YACHT_LEAD_ALLOCATION_EDIT,
                        ]),
                        'Yacht',
                        route('lead-allocation-dashboard', ['quoteType' => QuoteTypes::YACHT]),
                        fn ($s) => $s->attributes(['icon' => 'yacht'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_DASHBOARD,
                            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_VIEW_ONLY,
                            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_EDIT,
                        ]),
                        'Savings',
                        route('lead-allocation-dashboard', ['quoteType' => QuoteTypes::SAVINGS]),
                        fn ($s) => $s->attributes(['icon' => 'savings'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_DASHBOARD,
                            PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_VIEW_ONLY,
                            PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_EDIT,
                        ]),
                        'Group Medical',
                        route('lead-allocation-dashboard', ['quoteType' => QuoteTypes::GROUP_MEDICAL]),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        // CLAIM_ALLOCATION_DASHBOARD
                        auth()->user()->can(PermissionsEnum::CLAIM_ALLOCATION_DASHBOARD)
                            && ! getAppStorageValueByKey(ApplicationStorageEnums::DISABLE_CLAIMS_MODULE, false, useCache: true),
                        'Claims',
                        route('claim-allocation-dashboard'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::CYBER_LEAD_ALLOCATION_DASHBOARD,
                            PermissionsEnum::CYBER_LEAD_ALLOCATION_VIEW_ONLY,
                            PermissionsEnum::CYBER_LEAD_ALLOCATION_EDIT,
                        ]),
                        'Cyber',
                        route('lead-allocation-dashboard', ['quoteType' => QuoteTypes::CYBER]),
                        fn ($s) => $s->attributes(['icon' => 'cyber'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::DEVICE_LEAD_ALLOCATION_DASHBOARD),
                        'Device',
                        route('lead-allocation-dashboard', ['quoteType' => QuoteTypes::DEVICE]),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    );
            });
        }

        if (auth()->user()->can(PermissionsEnum::BUY_LEADS)) {
            $nav = $nav->add('Buy Leads', '', function (Section $section) {
                $section
                    ->addIf(
                        true,
                        'Buy Leads Request',
                        route('buy-leads.request.show'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        true,
                        'Buy Leads Tracking',
                        route('buy-leads.request.tracking'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::BUY_LEADS_EXPORT),
                        'Export Buy Leads',
                        route('buy-leads.request.export'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    );
            });
        }

        if (auth()->user()->can(PermissionsEnum::ActivitiesList)) {
            $nav = $nav->add('Activities', route('activities.index'));
        }

        if (auth()->user()->can(PermissionsEnum::CLAIM_LIST)
            && ! getAppStorageValueByKey(ApplicationStorageEnums::DISABLE_CLAIMS_MODULE, false, useCache: true)) {
            $nav = $nav->add('Services', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CLAIM_LIST),
                        'Claims',
                        route('claims.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    );
            });
        }

        if (auth()->user()->can(PermissionsEnum::SEARCH_ALL_LEAD_LOB)) {
            $nav = $nav->add('Search', route('search-leads'));
        }

        if (auth()->user()->can(PermissionsEnum::SAGE_PROCESS_ISSUE_MANAGEMENT)) {
            $nav = $nav->add('Sage Failed Leads', route('sage-failed-processes.index'));
        }

        /* personal quotes section */
        $nav = $nav->add('Personal Quotes', '', function (Section $section) {
            $section
                ->addIf(
                    (auth()->user()->hasAnyPermission([PermissionsEnum::CarQuotesList, PermissionsEnum::CarQuoteSearch, PermissionsEnum::CAR_REVIVAL_QUOTE_LIST]) || (userHasProduct(quoteTypeCode::Car) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))),
                    'Car',
                    route('car.index'),
                    fn ($s) => $s
                        ->attributes(['icon' => 'car'])
                        ->addIf(
                            (auth()->user()->can(PermissionsEnum::CarQuoteSearch)),
                            'Search',
                            route('car-quotes-search'),
                            fn ($s) => $s->attributes(['icon' => 'car'])
                        )
                        ->addIf(
                            (auth()->user()->can(PermissionsEnum::CarQuotesList) || (userHasProduct(quoteTypeCode::Car) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))),
                            'Lead List',
                            route('car.index'),
                            fn ($s) => $s->attributes(['icon' => 'car'])
                        )
                        ->addIf(
                            (auth()->user()->can(PermissionsEnum::CAR_REVIVAL_QUOTE_LIST)),
                            'Revival Quotes',
                            route('carrevival-quotes-list'),
                            fn ($s) => $s->attributes(['icon' => 'car'])
                        ),
                )
                ->addIf(
                    (auth()->user()->hasAnyPermission(
                        PermissionsEnum::HealthQuotesList,
                        PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS,
                        PermissionsEnum::HEALTH_QUOTES_ACCESS,
                        PermissionsEnum::HEALTH_REVIVAL_QUOTES_LIST
                    ) || (userHasProduct(quoteTypeCode::Health) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))),
                    'Health Quotes',
                    route('health.index'),
                    fn ($s) => $s
                        ->attributes(['icon' => 'health'])
                        ->addIf(
                            auth()->user()->hasAnyPermission(
                                PermissionsEnum::HealthQuotesList,
                                PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS,
                                PermissionsEnum::HEALTH_QUOTES_ACCESS
                            ),
                            'Health Quotes',
                            route('health.index'),
                            fn ($s) => $s->attributes(['icon' => 'health'])
                        )

                        ->addIf(
                            auth()->user()->can(PermissionsEnum::HEALTH_REVIVAL_QUOTES_LIST),
                            'Health Revival Quotes',
                            route('health-revival-quotes-list'),
                            fn ($s) => $s->attributes(['icon' => 'health'])
                        ),
                )
                ->addIf(
                    (auth()->user()->can(PermissionsEnum::TravelQuotesList)
                        || (userHasProduct(quoteTypeCode::Travel) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))),
                    'Travel Quotes',
                    route('travel.index'),
                    fn ($s) => $s->attributes(['icon' => 'travel'])
                )
                ->addIf(
                    (auth()->user()->hasAnyPermission(
                        PermissionsEnum::LifeQuotesList,
                        PermissionsEnum::LIFE_REVIVAL_QUOTES_LIST)
                        || (userHasProduct(quoteTypeCode::Life) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))),
                    'Life Quotes',
                    route('life-quotes-list'),
                    fn ($s) => $s
                        ->attributes(['icon' => 'life'])
                        ->addIf(
                            auth()->user()->can(PermissionsEnum::LifeQuotesList),
                            'Life Quotes',
                            route('life-quotes-list'),
                            fn ($s) => $s->attributes(['icon' => 'life'])
                        )
                        ->addIf(
                            auth()->user()->can(PermissionsEnum::LIFE_REVIVAL_QUOTES_LIST),
                            'Life Revival Quotes',
                            route('life-revival-quotes-list'),
                            fn ($s) => $s->attributes(['icon' => 'life'])
                        ),
                )
                ->addIf((auth()->user()->can(PermissionsEnum::SAVINGS_QUOTES_LIST) || (userHasProduct(quoteTypeCode::SAVINGS) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))), 'Savings Quotes', route('savings-quotes-list'), fn ($s) => $s->attributes(['icon' => 'savings']))
                ->addIf(
                    (auth()->user()->hasAnyPermission([PermissionsEnum::HomeQuotesList, PermissionsEnum::HOME_REVIVAL_QUOTES_LIST])
                        || (userHasProduct(quoteTypeCode::Home) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))),
                    'Home Quotes',
                    route('home-quotes-list'),
                    fn ($s) => $s
                        ->attributes(['icon' => 'home'])
                        ->addIf(
                            (auth()->user()->can(PermissionsEnum::HomeQuotesList) || (userHasProduct(quoteTypeCode::Home) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))),
                            'Home Quotes',
                            route('home-quotes-list'),
                            fn ($s) => $s->attributes(['icon' => 'home'])
                        )
                        ->addIf(
                            auth()->user()->can(PermissionsEnum::HOME_REVIVAL_QUOTES_LIST),
                            'Home Revival Quotes',
                            route('home-revival-quotes-list'),
                            fn ($s) => $s->attributes(['icon' => 'home'])
                        ),
                )
                ->addIf((auth()->user()->can(PermissionsEnum::DEVICE_QUOTES_LIST) || (userHasProduct(quoteTypeCode::Device) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))), 'Smartphone Quotes', route('device-quotes-list'), fn ($s) => $s->attributes(['icon' => 'box']))
                ->addIf((auth()->user()->can(PermissionsEnum::PetQuotesList) || (userHasProduct(quoteTypeCode::Pet) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))), 'Pet Quotes', route('pet-quotes-list'), fn ($s) => $s->attributes(['icon' => 'pet']))
                ->addIf((auth()->user()->can(PermissionsEnum::BikeQuotesList) || (userHasProduct(quoteTypeCode::Bike) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))), 'Bike Quotes', route('bike-quotes-list'), fn ($s) => $s->attributes(['icon' => 'bike']))
                ->addIf((auth()->user()->can(PermissionsEnum::CycleQuotesList) || (userHasProduct(quoteTypeCode::Cycle) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))), 'Cycle Quotes', route('cycle-quotes-list'), fn ($s) => $s->attributes(['icon' => 'cycle']))
                ->addIf((auth()->user()->can(PermissionsEnum::YachtQuotesList) || (userHasProduct(quoteTypeCode::Yacht) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))), 'Yacht Quotes', route('yacht-quotes-list'), fn ($s) => $s->attributes(['icon' => 'yacht']))
                ->addIf((auth()->user()->can(PermissionsEnum::CYBER_QUOTES_LIST) || (userHasProduct(quoteTypeCode::CYBER) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))), 'Cyber Quotes', route('cyber-quotes-list'), fn ($s) => $s->attributes(['icon' => 'cyber']))
                ->addIf((auth()->user()->can(PermissionsEnum::JetskiQuotesList) || (userHasProduct(quoteTypeCode::Jetski) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))), 'Jetski Quotes', route('jetski-quotes-list'), fn ($s) => $s->attributes(['icon' => 'jetski']));
        });
        /* personal quotes section end */

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::GMQuotesList,
            PermissionsEnum::CorpLineQuotesList,
            PermissionsEnum::UtmLeadsSalesReport,
        ]) || ((userHasProduct(quoteTypeCode::GroupMedical) || userHasProduct(quoteTypeCode::CORPLINE)) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))) {
            $nav = $nav->add('Business Quotes', '', function (Section $section) {
                $section
                    ->addIf(
                        (auth()->user()->can(PermissionsEnum::GMQuotesList) || (userHasProduct(quoteTypeCode::GroupMedical) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))),
                        'Group Medical Quotes',
                        route('amt.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        (auth()->user()->can(PermissionsEnum::CorpLineQuotesList) || (userHasProduct(quoteTypeCode::CORPLINE) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))),
                        'CorpLine Quotes',
                        route('business.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    );
            });
        }
        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::UPLOAD_HEALTH_RATES,
            PermissionsEnum::UPLOAD_HEALTH_COVERAGES,
        ])) {
            $nav = $nav->add('Upload Rates & Coverages', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::UPLOAD_HEALTH_RATES,
                            PermissionsEnum::UPLOAD_HEALTH_COVERAGES,
                        ]),
                        'Health',
                        route('upload-rates'),
                        fn ($s) => $s
                            ->attributes(['icon' => 'health'])
                            ->addIf(
                                auth()->user()->hasPermissionTo(PermissionsEnum::UPLOAD_HEALTH_COVERAGES),
                                'Coverages',
                                route('upload-coverages'),
                                fn ($s) => $s->attributes(['icon' => 'health'])
                            )
                            ->addIf(
                                auth()->user()->hasPermissionTo(PermissionsEnum::UPLOAD_HEALTH_RATES),
                                'Rates',
                                route('upload-rates'),
                                fn ($s) => $s->attributes(['icon' => 'health'])
                            ),

                    );
            });
        }
        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::GMQuotesList,
            PermissionsEnum::CorpLineQuotesList,
            PermissionsEnum::VehicleValuationList,
        ])) {
            $nav = $nav->add('Valuation', '', function (Section $section) {
                $section
                    ->add('Valuation', route('valuation'), fn ($s) => $s->attributes(['icon' => 'car']))
                    ->add('Vehicle Depreciation', route('vehicledepreciation.index'), fn ($s) => $s->attributes(['icon' => 'car']));
            });
        }

        // if (auth()->user()->can(PermissionsEnum::DiscountManagement)) {
        //     $nav = $nav->add('Discount Management', '', function (Section $section) {
        //         $section
        //             ->add('Base Discount', '/discount/base', fn ($s) => $s->attributes(['icon' => 'box']))
        //             ->add('Age Discount', '/discount/age', fn ($s) => $s->attributes(['icon' => 'box']));
        //     });
        // }

        if (auth()->user()->canAny([PermissionsEnum::TransAppList, PermissionsEnum::TransAppCreate, PermissionsEnum::TransAppEdit, PermissionsEnum::TRANSAPP_SEARCH])) {
            $nav = $nav->add('Trans App', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TRANSAPP_SEARCH),
                        'Search Transaction',
                        route('home'),
                        fn ($s) => $s->attributes(['icon' => 'box', 'external' => true])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TransAppCreate),
                        'Create Transaction',
                        route('transaction.create'),
                        fn ($s) => $s->attributes(['icon' => 'box', 'external' => true])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TransAppEdit),
                        'Cancel & Re-Issue Transaction',
                        route('reissue_view'),
                        fn ($s) => $s->attributes(['icon' => 'box', 'external' => true])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TransAppEdit),
                        'Cancel Transaction (without Re-Issue)',
                        route('cancel_view'),
                        fn ($s) => $s->attributes(['icon' => 'box', 'external' => true])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TransAppList),
                        'Transaction List',
                        route('transaction.index'),
                        fn ($s) => $s->attributes(['icon' => 'box', 'external' => true])
                    );
            });
        }

        if (auth()->user()->canAny([
            PermissionsEnum::CustomersList,
            PermissionsEnum::CustomersUpload,
            PermissionsEnum::LEADS_BY_EMAIL,
        ])
        ) {
            $nav = $nav->add('Customers', '', function (Section $section) {
                $section
                    ->addIf(auth()->user()->can(PermissionsEnum::CustomersList), 'Search', route('customers-list'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CustomersUpload),
                        'Uploads',
                        route('customer.upload'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(auth()->user()->canAny([PermissionsEnum::LEADS_BY_EMAIL, PermissionsEnum::CustomersList]), 'Leads by Email', route('leads-by-email'), fn ($s) => $s->attributes(['icon' => 'box']));
            });
        }

        if (auth()->user()->canAny([
            PermissionsEnum::RenewalsUpload,
            PermissionsEnum::RenewalsUploadedLeadList,
            PermissionsEnum::RenewalsUploadUpdate,
            PermissionsEnum::RenewalsBatches,
            PermissionsEnum::RENEWAL_UPLOAD_NONMOTOR,
            PermissionsEnum::RENEWALS_BATCHES_NONMOTOR,
        ])) {
            $nav = $nav->add('Renewals', '', function (Section $section) {
                $section
                    ->addIf(auth()->user()->can(PermissionsEnum::RenewalsUpload), 'Upload & Create', route('renewals-upload-create'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(auth()->user()->can(PermissionsEnum::RenewalsUploadedLeadList), 'Uploaded Leads', route('renewals-uploaded-leads-list'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(auth()->user()->can(PermissionsEnum::RenewalsUploadUpdate), 'Motor Upload & Update', route('renewals-upload-update'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(auth()->user()->can(PermissionsEnum::RENEWAL_UPLOAD_NONMOTOR), 'NonMotor Upload & Update', route('non-motor-renewals-upload-update'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(auth()->user()->can(PermissionsEnum::RenewalsBatches), 'Batches', route('renewals-batches'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(auth()->user()->can(PermissionsEnum::RENEWALS_BATCHES_NONMOTOR), 'NonMotor Batches', route('renewals-batches-nonmotor'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(PermissionsEnum::RenewalsBatches || PermissionsEnum::RenewalsUploadUpdate || PermissionsEnum::RenewalsUploadedLeadList || PermissionsEnum::RenewalsUpload, 'Search', route('renewals-batches-search'), fn ($s) => $s->attributes(['icon' => 'box']));
            });
        }

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::AMLList,
            PermissionsEnum::EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS,
        ])) {
            $nav = $nav->add('AML', '', function (Section $section) {
                $section
                    ->add('All Quotes', route('aml.index'), fn ($s) => $s->attributes(['icon' => 'box']));
            });
        }

        if (
            auth()->user()->hasAnyPermission([
                PermissionsEnum::EMBEDDED_PRODUCT_CONFIG,
            ])
        ) {
            $nav = $nav->add('Embedded Products', '', function (Section $section) {
                $section
                    ->add('All Products', route('embedded-products.index'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->add('Reports', route('embedded-products.reports'), fn ($s) => $s->attributes(['icon' => 'bar']));
            });
        }

        $nav = $nav->addIf(auth()->user()->hasAnyPermission([PermissionsEnum::VIEW_LEGACY_DETAILS, PermissionsEnum::VIEW_ALL_LEADS]), 'Legacy Policies', route('legacy-policy.index'));

        if (auth()->user()->can(PermissionsEnum::TeleMarketingList)) {
            $nav = $nav->add('Telemarketing', '', function (Section $section) {
                $section
                    ->add('TM Leads', route('tmleads-list'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TMUploadLeadsList),
                        'Upload TM Leads',
                        route('tmuploadlead-list'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'TM Type of Insurance',
                        route('tminsurancetype.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'TM Lead Status',
                        route('tmleadstatus.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    );
            });
        }
        $adminMenuPermissions = [
            PermissionsEnum::UsersList,
            PermissionsEnum::RoleList,
            PermissionsEnum::TeamsList,
            PermissionsEnum::RENEWAL_BATCHES_LIST,
            PermissionsEnum::COMMERCIAL_KEYWORDS,
            PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES,
            PermissionsEnum::RULE_CONFIG_LIST,
            PermissionsEnum::QUAD_CONFIG_LIST,
            PermissionsEnum::TIER_CONFIG_LIST,
            PermissionsEnum::TeamThresholdView,
            PermissionsEnum::COMMERCIAL_KEYWORDS,
            PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES,
            PermissionsEnum::QUOTE_SYNC_LOGS,
            PermissionsEnum::ILA_CONFIG_ALL_LOB,
        ];
        if (auth()->user()->hasAnyPermission($adminMenuPermissions) || auth()->user()->hasAnyRole([RolesEnum::Engineering, RolesEnum::Admin])) {
            $nav = $nav->add('Admin', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::QUOTE_SYNC_LOGS),
                        'Quote Sync',
                        route('admin.quotesync'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::UsersList),
                        'Users',
                        route('users.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyRole([RolesEnum::Engineering, RolesEnum::Admin]),
                        'User Status Logs',
                        route('admin.user-status-logs.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyRole([RolesEnum::Engineering, RolesEnum::Admin]),
                        'Activity Logs',
                        route('admin.activity-logs.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::RoleList),
                        'Roles',
                        route('roles.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::PERMISSION_LIST),
                        'Permissions',
                        route('permissions.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::DEPARTMENT_LIST),
                        'Departments',
                        url('admin/departments'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::BRANCHES),
                        'Branches',
                        url('admin/branches'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::BRANCH_ASSIGNMENTS),
                        'Branch Assignment',
                        url('admin/branch-assignments'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TeamsList),
                        'Teams',
                        route('team.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::RENEWAL_BATCHES_LIST),
                        'Renewal Batches',
                        route('renewal-batches-list'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyRole([RolesEnum::Engineering]),
                        'Allocation Audit',
                        route('admin.allocation-audit.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyRole([RolesEnum::LeadPool, RolesEnum::SeniorManagement, RolesEnum::Engineering]),
                        'Buy Lead Config',
                        route('admin.buy-leads.config.show'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::BUY_LEADS_ADMIN),
                        'Buy Lead Requests',
                        route('admin.buy-leads.requests.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyRole([RolesEnum::SeniorManagement, RolesEnum::Engineering, RolesEnum::Admin]),
                        'Private Client Config',
                        route('admin.private-client-config.show'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyRole([RolesEnum::Engineering]) && getAppStorageValueByKey(ApplicationStorageEnums::BENCHMARKING_ENABLED, 0, useCache: true) == 1,
                        'Query Benchmarker',
                        route('admin.benchmarker.query.show'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyRole([RolesEnum::Engineering]),
                        'System Health',
                        route('admin.system-health.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyRole([RolesEnum::Engineering, RolesEnum::Admin]),
                        'Permissions Docs',
                        url('/permissions-docs/index.php'),
                        fn ($s) => $s->attributes(['icon' => 'box', 'external' => true, 'target' => '_blank'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::RULE_CONFIG_LIST,
                            PermissionsEnum::QUAD_CONFIG_LIST,
                            PermissionsEnum::TIER_CONFIG_LIST,
                            PermissionsEnum::TeamThresholdView,
                            PermissionsEnum::COMMERCIAL_KEYWORDS,
                            PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES,
                            PermissionsEnum::ILA_CONFIG_ALL_LOB,
                        ]),
                        'Allocation Config',
                        route('tiers.index'),
                        fn ($s) => $s
                            ->attributes(['icon' => 'box'])
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::TIER_CONFIG_LIST),
                                'Tiers',
                                route('tiers.index'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::QUAD_CONFIG_LIST),
                                'Quadrants',
                                route('quadrants.index'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::RULE_CONFIG_LIST),
                                'Rules',
                                route('rule.index'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::TeamThresholdView),
                                'Team Threshold',
                                route('allocation-threshold.index'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::COMMERCIAL_KEYWORDS),
                                'Commercial Keywords',
                                route('admin.commercial.keywords'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES),
                                'Configure Commercial Vehicles',
                                route('admin.configure.commerical.vehicles'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::SIC_HEALTH_CONFIG),
                                'Configure SIC Health',
                                route('admin.sic-health-config.index'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::ILA_CONFIG_ALL_LOB),
                                'ILA Configuration',
                                route('admin.allocation-configuration.index'),
                                fn ($s) => $s->attributes(['icon' => 'settings'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::NATIONALITY_ALLOCATION_CONFIG),
                                'Nationality Allocation',
                                route('admin.nationality-allocation-config.index'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::NATIONALITY_POOL_CONFIG),
                                'GBP Eligible Nationalities',
                                route('admin.nationality-pool-config.index'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                    );
            });
        }

        if (auth()->user()->can(PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS)) {
            $nav = $nav->add('InstantAlfred Chat Logs', route('instant-alfred.index'));
        }

        return $nav;
    }
}
