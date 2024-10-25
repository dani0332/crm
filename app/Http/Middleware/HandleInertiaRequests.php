<?php

namespace App\Http\Middleware;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\DocumentTypeEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\Kyc;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentAllocationStatus;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\ProductionProcessTooltipEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteIssuanceStatusEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendPolicyTypeEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TravelQuoteEnum;
use App\Models\PolicyIssuanceStatus;
use App\Repositories\PaymentRepository;
use App\Services\ActivitiesService;
use App\Services\ApplicationStorageService;
use App\Services\LeadsCountService;
use App\Services\SplitPaymentService;
use App\Services\UserService;
use Illuminate\Http\Request;
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
            $vatValue = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::VAT_VALUE);
        }

        return [
            ...parent::share($request),
            'auth.user' => fn () => $request->user()
                ? $request->user()->only('id', 'name', 'email', 'profile_photo_path', 'status')
                : null,
            'auth.permissions' => fn () => $permissions,
            'auth.roles' => fn () => $roles,
            'sidebar' => fn () => $this->buildNavigation()->tree(),
            'location' => fn () => $request->url(),
            'permissionsEnum' => PermissionsEnum::asArray(),
            'rolesEnum' => RolesEnum::asArray(),
            'insuranceProviderCodeEnum' => InsuranceProvidersEnum::asArray(),
            'paymentStatusEnum' => PaymentStatusEnum::asArray(),
            'documentTypeEnum' => DocumentTypeEnum::asArray(),
            'sendPolicyTypeEnum' => SendPolicyTypeEnum::asArray(),
            'quoteTypeCodeEnum' => quoteTypeCode::asArray(),
            'travelQuoteEnum' => TravelQuoteEnum::asArray(),
            'quoteIssuanceStatusEnum' => QuoteIssuanceStatusEnum::asArray(),
            'quoteBusinessTypeCode' => quoteBusinessTypeCode::asArray(),
            'leadSource' => LeadSourceEnum::asArray(),
            'flash' => fn () => $this->shareFlashData($request),
            'baseUrl' => url('/'),
            'cdnPath' => config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/',
            'appEnv' => config('constants.APP_ENV'),
            'pusherKey' => config('constants.VITE_PUSHER_APP_KEY'),
            'epLink' => config('constants.AFIA_WEBSITE_DOMAIN'),
            'vat' => ApplicationStorageEnums::VAT,
            'paymentMethodsEnum' => PaymentMethodsEnum::asArray(),
            'sendUpdateLogStatusEnum' => SendUpdateLogStatusEnum::asArray(),
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
            'amlStatusEnum' => AMLStatusCode::asArray(),
            'totalQuotesCount' => LeadsCountService::getLeadCount(),
            'im_logo' => getIMLogo(),
            'authorisePaymentCount' => app(PaymentRepository::class)->getAuthorisePaymentCount($userId = null),
            'checkAuthUserRole' => checkAuthUserRole(),
            'quoteSegments' => QuoteSegmentEnum::withLabels(),
            'paymentLookups' => app(SplitPaymentService::class)->getPaymentLookups(),
            'vatValue' => $vatValue,
            'productionProcessTooltipEnum' => ProductionProcessTooltipEnum::asArray(),
            'policyIssuanceStatus' => PolicyIssuanceStatus::active()->get(),
            'policyIssuanceStatusEnum' => PolicyIssuanceStatusEnum::asArray(),
            'paymentAllocationStatus' => PaymentAllocationStatus::asArray(),
            'lookupsEnum' => getLookupsEnum(),
            'kycEnums' => Kyc::asArray(),
            'documentTypeCodeEnum' => DocumentTypeCode::asArray(),
            'paymentFrequencyEnum' => PaymentFrequency::asArray(),
            'pendingActivityCount' => app(ActivitiesService::class)->getPendingActivityCount(),
            'quoteTypes' => QuoteTypes::allTypesWithIds(),
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
                        auth()->user()->can(PermissionsEnum::MAIN_DASHBOARD_VIEW),
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
                PermissionsEnum::MANAGEMENT_REPORT,
            ],
            PermissionsEnum::getAdvisorConversionReportPermissions(),
            PermissionsEnum::getAdvisorDistributionReportPermissions()
        ))) {
            $nav = $nav->add('Reports', '', function (Section $section) {
                $section
                    ->addIf(auth()->user()->can(PermissionsEnum::CONVERSION_AS_AT_REPORT), 'Conversion As At Report', route('conversion-as-at-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->hasAnyPermission(PermissionsEnum::getAdvisorConversionReportPermissions()), 'Advisor Conversion', route('advisor-conversion-report-view'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::ADVISOR_PERFORMANCE_REPORT_VIEW), 'Advisor Performance', route('advisor-performance-report-view'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->hasAnyPermission(PermissionsEnum::getAdvisorDistributionReportPermissions()), 'Advisor Distribution', route('advisor-distribution-report-view'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::LEAD_DISTRIBUTION_REPORT_VIEW), 'Lead Distribution', route('lead-distribution-report-view'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::REVIVAL_CONVERSION_REPORT_VIEW), 'Revival Conversion', route('revival-conversion-report-view'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::UtmLeadsSalesReport), 'UTM Report', route('utm-leads-sales-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::RENEWAL_BATCH_REPORT), 'Daily Renewal Report', route('renewal-batch-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::MANAGER_AUTHORISED_PAYMENT_SUMMARY), 'Authorised Payment Summary', route('authorized-payment-summary', [], false), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::MANAGEMENT_REPORT), 'Management Report', route('management-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(app(UserService::class)->isAllowedToShowLeadListReport(), 'Lead List Report', route('lead-list-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::STALE_LEADS_REPORT), 'Stale Leads Report', route('stale-leads-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::PIPELINE_REPORT), 'Pipeline Report', route('pipeline-report'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::TOTAL_PREMIUM_LEADS_SALES_REPORT), 'Total Premium Report', route('total-premium-leads-sales-report'), fn ($s) => $s->attributes(['icon' => 'bar']));
            });
        }

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::CAR_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::HEALTH_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::UtmLeadsSalesReport,
        ])) {
            $nav = $nav->add('Lead Allocation', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::HEALTH_LEAD_ALLOCATION_DASHBOARD),
                        'Health',
                        route('lead-allocation.index'),
                        fn ($s) => $s->attributes(['icon' => 'health'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CAR_LEAD_ALLOCATION_DASHBOARD),
                        'Car',
                        route('car-lead-allocation.index'),
                        fn ($s) => $s->attributes(['icon' => 'car'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TRAVEL_SIC_ALLOCATION),
                        'Travel',
                        route('travel-lead-allocation.index'),
                        fn ($s) => $s->attributes(['icon' => 'travel'])
                    );
            });
        }

        if (auth()->user()->can(PermissionsEnum::ActivitiesList)) {
            $nav = $nav->add('Activities', route('activities.index'));
        }

        /* personal quotes section */
        $nav = $nav->add('Personal Quotes', '', function (Section $section) {
            $section
                ->addIf(
                    auth()->user()->hasAnyPermission([PermissionsEnum::CarQuotesList, PermissionsEnum::CarQuoteSearch, PermissionsEnum::CAR_REVIVAL_QUOTE_LIST]),
                    'Car',
                    route('car.index'),
                    fn ($s) => $s
                        ->attributes(['icon' => 'car'])
                        ->addIf(
                            auth()->user()->can(PermissionsEnum::CarQuoteSearch),
                            'Search',
                            route('car-quotes-search'),
                            fn ($s) => $s->attributes(['icon' => 'car'])
                        )
                        ->addIf(
                            auth()->user()->can(PermissionsEnum::CarQuotesList),
                            'Lead List',
                            route('car.index'),
                            fn ($s) => $s->attributes(['icon' => 'car'])
                        )
                        ->addIf(
                            auth()->user()->can(PermissionsEnum::CAR_REVIVAL_QUOTE_LIST),
                            'Revival Quotes',
                            route('carrevival-quotes-list'),
                            fn ($s) => $s->attributes(['icon' => 'car'])
                        ),
                )
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
                    auth()->user()->can(PermissionsEnum::TravelQuotesList),
                    'Travel Quotes',
                    route('travel.index'),
                    fn ($s) => $s->attributes(['icon' => 'travel'])
                )
                ->addIf(
                    auth()->user()->can(PermissionsEnum::LifeQuotesList),
                    'Life Quotes',
                    route('life-quotes-list'),
                    fn ($s) => $s->attributes(['icon' => 'life'])
                )
                ->addIf(
                    auth()->user()->can(PermissionsEnum::HomeQuotesList),
                    'Home Quotes',
                    route('home.index'),
                    fn ($s) => $s->attributes(['icon' => 'home'])
                )
                ->addIf(in_array(quoteTypeCode::Pet, newUi()) && auth()->user()->can(PermissionsEnum::PetQuotesList), 'Pet Quotes', route('pet-quotes-list'), fn ($s) => $s->attributes(['icon' => 'pet']))
                ->addIf(in_array(quoteTypeCode::Bike, newUi()) && auth()->user()->can(PermissionsEnum::BikeQuotesList), 'Bike Quotes', route('bike-quotes-list'), fn ($s) => $s->attributes(['icon' => 'bike']))
                ->addIf(in_array(quoteTypeCode::Cycle, newUi()) && auth()->user()->can(PermissionsEnum::CycleQuotesList), 'Cycle Quotes', route('cycle-quotes-list'), fn ($s) => $s->attributes(['icon' => 'cycle']))
                ->addIf(in_array(quoteTypeCode::Yacht, newUi()) && auth()->user()->can(PermissionsEnum::YachtQuotesList), 'Yacht Quotes', route('yacht-quotes-list'), fn ($s) => $s->attributes(['icon' => 'yacht']))
                ->addIf(in_array(quoteTypeCode::Jetski, newUi()) && auth()->user()->can(PermissionsEnum::JetskiQuotesList), 'Jetski Quotes', route('jetski-quotes-list'), fn ($s) => $s->attributes(['icon' => 'jetski']));
        });
        /* personal quotes section end */

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::GMQuotesList,
            PermissionsEnum::CorpLineQuotesList,
            PermissionsEnum::UtmLeadsSalesReport,
        ])) {
            $nav = $nav->add('Business Quotes', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::GMQuotesList),
                        'Group Medical Quotes',
                        route('amt.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CorpLineQuotesList),
                        'CorpLine Quotes',
                        route('business.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
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

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::CAR_SOLD_LIST,
            PermissionsEnum::CAR_UNCONTACTABLE_LIST,
        ])) {
            $nav = $nav->add('Car Sold / Uncon', '', function (Section $section) {
                $section
                    ->addIf(auth()->user()->hasPermissionTo(PermissionsEnum::CAR_SOLD_LIST), 'Car Sold', route('car-sold-list'), fn ($s) => $s->attributes(['icon' => 'car']))
                    ->addIf(auth()->user()->hasPermissionTo(PermissionsEnum::CAR_UNCONTACTABLE_LIST), 'Car Uncontactable', route('car-uncontactable-list'), fn ($s) => $s->attributes(['icon' => 'car']));
            });
        }

        // if (auth()->user()->can(PermissionsEnum::DiscountManagement)) {
        //     $nav = $nav->add('Discount Management', '', function (Section $section) {
        //         $section
        //             ->add('Base Discount', '/discount/base', fn ($s) => $s->attributes(['icon' => 'box']))
        //             ->add('Age Discount', '/discount/age', fn ($s) => $s->attributes(['icon' => 'box']));
        //     });
        // }

        if (auth()->user()->canAny([PermissionsEnum::TransAppList, PermissionsEnum::TransAppCreate, PermissionsEnum::TransAppEdit])) {
            $nav = $nav->add('Trans App', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TransAppCreate),
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

        if (auth()->user()->can(PermissionsEnum::CustomersList)) {
            $nav = $nav->add('Customers', '', function (Section $section) {
                $section
                    ->add('Search', route('customers-list'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CustomersUpload),
                        'Uploads',
                        route('customer.upload'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    );
            });
        }

        if (auth()->user()->canAny([PermissionsEnum::RenewalsUpload, PermissionsEnum::RenewalsUploadedLeadList, PermissionsEnum::RenewalsUploadUpdate, PermissionsEnum::RenewalsBatches])) {
            $nav = $nav->add('Renewals', '', function (Section $section) {
                $section
                    ->addIf(auth()->user()->can(PermissionsEnum::RenewalsUpload), 'Upload & Create', route('renewals-upload-create'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(auth()->user()->can(PermissionsEnum::RenewalsUploadedLeadList), 'Uploaded Leads', route('renewals-uploaded-leads-list'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(auth()->user()->can(PermissionsEnum::RenewalsUploadUpdate), 'Upload & Update', route('renewals-upload-update'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(auth()->user()->can(PermissionsEnum::RenewalsBatches), 'Batches', route('renewals-batches'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(PermissionsEnum::RenewalsBatches || PermissionsEnum::RenewalsUploadUpdate || PermissionsEnum::RenewalsUploadedLeadList || PermissionsEnum::RenewalsUpload, 'Search', route('renewals-batches-search'), fn ($s) => $s->attributes(['icon' => 'box']));
            });
        }

        if (auth()->user()->can(PermissionsEnum::AMLList)) {
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

        $nav = $nav->addIf(auth()->user()->can(PermissionsEnum::VIEW_LEGACY_DETAILS), 'Legacy Policies', route('legacy-policy.index'));

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
        ];
        if (auth()->user()->hasAnyPermission($adminMenuPermissions)) {
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
                        auth()->user()->can(PermissionsEnum::RoleList),
                        'Roles',
                        route('roles.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::DEPARTMENT_LIST),
                        'Departments',
                        url('admin/departments'),
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
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::RULE_CONFIG_LIST,
                            PermissionsEnum::QUAD_CONFIG_LIST,
                            PermissionsEnum::TIER_CONFIG_LIST,
                            PermissionsEnum::TeamThresholdView,
                            PermissionsEnum::COMMERCIAL_KEYWORDS,
                            PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES,
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
                    );
            });
        }

        if (auth()->user()->can(PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS)) {
            $nav = $nav->add('InstantAlfred Chat Logs', route('instant-alfred.logs'));
        }

        return $nav;
    }
}
