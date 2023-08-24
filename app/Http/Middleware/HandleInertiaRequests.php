<?php

namespace App\Http\Middleware;

use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        if (auth()->user()) {
            $permissions = auth()->user()->getAllPermissions()->pluck('name')->toArray();
            $roles = auth()->user()->getRoleNames()->toArray();
        }

        return array_merge(parent::share($request), [
            'auth.user' => fn () => $request->user()
                ? $request->user()->only('id', 'name', 'email')
                : null,
            'auth.permissions' => fn () => $permissions,
            'auth.roles' => fn () => $roles,
            'sidebar' => fn () => $this->buildNavigation()->tree(),
            'permissionsEnum' => PermissionsEnum::asArray(),
            'rolesEnum' => RolesEnum::asArray(),
            'quoteTypeCodeEnum' => quoteTypeCode::asArray(),
            'quoteBusinessTypeCode' => quoteBusinessTypeCode::asArray(),
            'flash' => fn () => $this->shareFlashData($request),
            'baseUrl' => url('/'),
        ]);
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
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $nav = app(Navigation::class)
            ->add('Home', url('/home'));

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::DashboardView,
            PermissionsEnum::TPL_DASHBOARD_VIEW,
            PermissionsEnum::COMPREHENSIVE_DASHBOARD_VIEW,
            PermissionsEnum::MAIN_DASHBOARD_VIEW,
            PermissionsEnum::UtmLeadsSalesReport,
        ])) {
            $nav = $nav->add('Dashboard', '', function (Section $section) {
                $section
                    ->add('Car Conversion', url('dashboard/car-conversion'), fn ($s) => $s->attributes(['icon' => 'car']))
                    ->add('Travel Conversion', url('dashboard/travel-conversion'), fn ($s) => $s->attributes(['icon' => 'travel']))
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TPL_DASHBOARD_VIEW),
                        'TPL Conversion',
                        url('/tpl-conversion-dashboard'),
                        fn ($s) => $s->attributes(['icon' => 'graph'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::COMPREHENSIVE_DASHBOARD_VIEW),
                        'Comprehensive Conversion',
                        url('/comprehensive-conversion-dashboard'),
                        fn ($s) => $s->attributes(['icon' => 'graph'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::MAIN_DASHBOARD_VIEW),
                        'Accumulative Dashboard',
                        url('/accumulative-dashboard'),
                        fn ($s) => $s->attributes(['icon' => 'graph'])
                    );
            });
        }

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::ADVISOR_CONVERSION_REPORT_VIEW,
            PermissionsEnum::ADVISOR_PERFORMANCE_REPORT_VIEW,
            PermissionsEnum::ADVISOR_DISTRIBUTION_REPORT_VIEW,
            PermissionsEnum::LEAD_DISTRIBUTION_REPORT_VIEW,
            PermissionsEnum::UtmLeadsSalesReport,
        ])) {
            $nav = $nav->add('Reports', '', function (Section $section) {
                $section
                    ->addIf(auth()->user()->can(PermissionsEnum::ADVISOR_CONVERSION_REPORT_VIEW), 'Advisor Conversion', route('advisor-conversion-report-view', [], false), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::ADVISOR_PERFORMANCE_REPORT_VIEW), 'Advisor Performance', route('advisor-performance-report-view', [], false), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::ADVISOR_DISTRIBUTION_REPORT_VIEW), 'Advisor Distribution', route('advisor-distribution-report-view', [], false), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::LEAD_DISTRIBUTION_REPORT_VIEW), 'Lead Distribution', route('lead-distribution-report-view', [], false), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->addIf(auth()->user()->can(PermissionsEnum::UtmLeadsSalesReport), 'UTM Report', route('utm-leads-sales-report', [], false), fn ($s) => $s->attributes(['icon' => 'bar']));
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
                        url('lead-allocation'),
                        fn ($s) => $s->attributes(['icon' => 'health'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CAR_LEAD_ALLOCATION_DASHBOARD),
                        'Car',
                        url('car-lead-allocation'),
                        fn ($s) => $s->attributes(['icon' => 'car'])
                    );
            });
        }

        if (auth()->user()->can(PermissionsEnum::ActivitiesList)) {
            $nav = $nav->add('Activities', url('/activities'));
        }

        /* personal quotes section */
        $nav = $nav->add('Personal Quotes', '', function (Section $section) {
            $section
                ->addIf(
                    auth()->user()->hasAnyPermission([PermissionsEnum::CarQuotesList, PermissionsEnum::CarQuoteSearch]),
                    'Car',
                    '/quotes/car',
                    fn ($s) => $s
                        ->attributes(['icon' => 'car'])
                        ->addIf(
                            auth()->user()->can(PermissionsEnum::CarQuoteSearch),
                            'Search',
                            '/personal-quotes/car/car-quotes-search',
                            fn ($s) => $s->attributes(['icon' => 'car'])
                        )
                        ->addIf(
                            auth()->user()->can(PermissionsEnum::CarQuotesList),
                            'Lead List',
                            '/quotes/car',
                            fn ($s) => $s->attributes(['icon' => 'car'])
                        ),
                )
                ->addIf(
                    auth()->user()->can(PermissionsEnum::HealthQuotesList),
                    'Health Quotes',
                    '/quotes/health',
                    fn ($s) => $s->attributes(['icon' => 'health'])
                )
                ->addIf(
                    auth()->user()->can(PermissionsEnum::TravelQuotesList),
                    'Travel Quotes',
                    '/quotes/travel',
                    fn ($s) => $s->attributes(['icon' => 'travel'])
                )
                ->addIf(
                    auth()->user()->can(PermissionsEnum::LifeQuotesList),
                    'Life Quotes',
                    '/quotes/life',
                    fn ($s) => $s->attributes(['icon' => 'life'])
                )
                ->addIf(
                    auth()->user()->can(PermissionsEnum::HomeQuotesList),
                    'Home Quotes',
                    '/quotes/home',
                    fn ($s) => $s->attributes(['icon' => 'home'])
                )
                ->addIf(in_array(quoteTypeCode::Pet, newUi()) && auth()->user()->can(PermissionsEnum::PetQuotesList), 'Pet Quotes', '/personal-quotes/pet', fn ($s) => $s->attributes(['icon' => 'pet']))
                ->addIf(in_array(quoteTypeCode::Bike, newUi()) && auth()->user()->can(PermissionsEnum::BikeQuotesList), 'Bike Quotes', '/personal-quotes/bike', fn ($s) => $s->attributes(['icon' => 'bike']))
                ->addIf(in_array(quoteTypeCode::Cycle, newUi()) && auth()->user()->can(PermissionsEnum::CycleQuotesList), 'Cycle Quotes', '/personal-quotes/cycle', fn ($s) => $s->attributes(['icon' => 'cycle']))
                ->addIf(in_array(quoteTypeCode::Yacht, newUi()) && auth()->user()->can(PermissionsEnum::YachtQuotesList), 'Yacht Quotes', '/personal-quotes/yacht', fn ($s) => $s->attributes(['icon' => 'yacht']))
                ->addIf(in_array(quoteTypeCode::Jetski, newUi()) && auth()->user()->can(PermissionsEnum::JetskiQuotesList), 'Jetski Quotes', '/personal-quotes/jetski', fn ($s) => $s->attributes(['icon' => 'jetski']));
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
                        url('/medical/amt'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CorpLineQuotesList),
                        'CorpLine Quotes',
                        url('/quotes/business'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    );
            });
        }

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::GMQuotesList,
            PermissionsEnum::CorpLineQuotesList,
        ])) {
            $nav = $nav->add('Car', '', function (Section $section) {
                $section
                    ->add('Valuation', '/valuation/calculatevaluation', fn ($s) => $s->attributes(['icon' => 'car']))
                    ->add('Vehicle Depreciation', '/valuation/vehicledepreciation', fn ($s) => $s->attributes(['icon' => 'car']));
            });
        }

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::CarSoldList,
            PermissionsEnum::CarUncontactableList,
        ])) {

            $nav = $nav->add('Car Sold / Uncon', '', function (Section $section) {
                $section
                    ->addIf(auth()->user()->hasPermissionTo(PermissionsEnum::CarSoldList), 'Car Sold', '/quotes/car-sold', fn ($s) => $s->attributes(['icon' => 'car']))
                    ->addIf(auth()->user()->hasPermissionTo(PermissionsEnum::CarUncontactableList), 'Car Uncontactable', '/quotes/car-uncontactable', fn ($s) => $s->attributes(['icon' => 'car']));
            });
        }


        // if (auth()->user()->can(PermissionsEnum::DiscountManagement)) {
        //     $nav = $nav->add('Discount Management', '', function (Section $section) {
        //         $section
        //             ->add('Base Discount', '/discount/base', fn ($s) => $s->attributes(['icon' => 'box']))
        //             ->add('Age Discount', '/discount/age', fn ($s) => $s->attributes(['icon' => 'box']));
        //     });
        // }

        if (auth()->user()->can(PermissionsEnum::TransAppList)) {
            $nav = $nav->add('Trans App', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TransAppCreate),
                        'Search Transaction',
                        '/transapp/home',
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TransAppCreate),
                        'Create Transaction',
                        '/transapp/transaction/create',
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TransAppEdit),
                        'Cancel & Re-Issue Transaction',
                        '/transapp/re-issue-transaction',
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TransAppEdit),
                        'Cancel Transaction (without Re-Issue)',
                        '/transapp/cancel-transaction',
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->add('Transaction List', '/transapp/transaction', fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'Admin',
                        route('insurancecompany.index'),
                        fn ($s) => $s
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::InsuranceCompanyList),
                                'Insurance Companies',
                                route('insurancecompany.index'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::ReasonList),
                                'Reasons',
                                route('reason.index'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::StatusList),
                                'Status',
                                route('status.index'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::PaymentModeList),
                                'Payment Modes',
                                route('paymentmode.index'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                    );
            });
        }

        if (auth()->user()->can(PermissionsEnum::CustomersList)) {
            $nav = $nav->add('Customers', '', function (Section $section) {
                $section
                    ->add('Search', url('customer'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CustomersUpload),
                        'Uploads',
                        url('customer-upload'),
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
                    ->addIf(auth()->user()->can(PermissionsEnum::RenewalsBatches), 'Batches', route('renewals-batches'), fn ($s) => $s->attributes(['icon' => 'box']));
            });
        }

        if (auth()->user()->can(PermissionsEnum::ClaimList)) {
            $nav = $nav->add('Claims', '', function (Section $section) {
                $section
                    ->add('Claims List', url('claim/claims'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'Type of Insurance',
                        url('claim/typeofinsurance'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'Sub Type of Insurance',
                        url('claim/subtypeofinsurance'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'Claim Status',
                        url('claim/claimsstatus'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'Car Repair Coverage',
                        url('claim/carrepaircoverage'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'Car Repair Type',
                        url('claim/carrepairtype'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    );
            });
        }

        if (auth()->user()->can(PermissionsEnum::AMLList)) {
            $nav = $nav->add('AML', '', function (Section $section) {
                $section
                    ->add('All Quotes', url('kyc/aml'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->add('Downloaded Sanction Lists', url('kyc/aml/download/history'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->add('Upload UAE List', url('kyc/aml/upload/uae'), fn ($s) => $s->attributes(['icon' => 'box']));
            });
        }

        if (auth()->user()->hasAnyRole([RolesEnum::Admin, RolesEnum::BetaUser, RolesEnum::Engineering])) {
            $nav = $nav->add('Embedded Products', url('embedded-products'));
        }
        if (auth()->user()->can(PermissionsEnum::TeleMarketingList)) {
            $nav = $nav->add('Telemarketing', '', function (Section $section) {
                $section
                    ->add('TM Leads', url('telemarketing/tmleads'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TMUploadLeadsList),
                        'Upload TM Leads',
                        url('telemarketing/tmuploadlead'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'TM Type of Insurance',
                        url('telemarketing/tminsurancetype'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'TM Lead Status',
                        url('telemarketing/tmleadstatus'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    );
            });
        }
        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::UsersList, PermissionsEnum::RoleList,
            PermissionsEnum::TeamsList,
        ])) {
            $nav = $nav->add('Admin', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::UsersList),
                        'Users',
                        url('admin/users'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::RoleList),
                        'Roles',
                        url('admin/roles'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::TeamsList),
                        'Teams',
                        url('generic/team'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->hasAnyPermission([
                            PermissionsEnum::RULE_CONFIG_LIST,
                            PermissionsEnum::QUAD_CONFIG_LIST,
                            PermissionsEnum::TIER_CONFIG_LIST,
                        ]),
                        'Allocation Config',
                        url('generic/tier'),
                        fn ($s) => $s
                            ->attributes(['icon' => 'box'])
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::TIER_CONFIG_LIST),
                                'Tiers',
                                url('generic/tier'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::QUAD_CONFIG_LIST),
                                'Quadrants',
                                url('generic/quadrant'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                            ->addIf(
                                auth()->user()->can(PermissionsEnum::RULE_CONFIG_LIST),
                                'Rules',
                                url('generic/rule'),
                                fn ($s) => $s->attributes(['icon' => 'box'])
                            )
                    );
            });
        }

        return $nav;
    }
}
