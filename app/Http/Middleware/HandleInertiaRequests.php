<?php

namespace App\Http\Middleware;

use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
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
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $nav = app(Navigation::class)
            ->add('Home', url('/leadsearch'));

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::DashboardView,
            PermissionsEnum::TPL_DASHBOARD_VIEW,
            PermissionsEnum::COMPREHENSIVE_DASHBOARD_VIEW,
            PermissionsEnum::MAIN_DASHBOARD_VIEW,
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

        if (auth()->user()->hasRole(RolesEnum::BetaUser) && auth()->user()->hasAnyRole([
            RolesEnum::CarDeputyManager,
            RolesEnum::CarAdvisor,
            RolesEnum::Admin,
            RolesEnum::CarManager,
        ])) {
            $nav = $nav->add('Reports', '', function (Section $section) {
                $section
                    ->add('Advisor Conversion', url('reports/advisor-conversion'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->add('Advisor Performance', url('reports/advisor-performance'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->add('Advisor Distribution', url('reports/advisor-distribution'), fn ($s) => $s->attributes(['icon' => 'bar']))
                    ->add('Lead Distribution', url('reports/lead-distribution'), fn ($s) => $s->attributes(['icon' => 'bar']));
            });
        }

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::CAR_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::HEALTH_LEAD_ALLOCATION_DASHBOARD,
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

        if (auth()->check() && auth()->user()->hasMyLeadAccess()) {
            $nav = $nav->add('My Leads', url('/myleads'));
        }

        if (auth()->user()->can(PermissionsEnum::RewardList)) {
            $nav = $nav->add('Rewards', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::PartnersList),
                        'Partners List',
                        url('rewards/partner'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->add('Rewards List', url('rewards/reward'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::RewardCategoriesList),
                        'Reward Categories',
                        url('rewards/reward-categories'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::RewardTagsList),
                        'Reward Tags',
                        url('rewards/reward-tags'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::RewardSliderList),
                        'Reward Slider',
                        url('rewards/reward-sliders'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    );
            });
        }

        if (auth()->user()->can(PermissionsEnum::ActivitiesList)) {
            $nav = $nav->add('Activities', url('/activities'));
        }

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::CarQuotesList, PermissionsEnum::HealthQuotesList,
            PermissionsEnum::TravelQuotesList, PermissionsEnum::LifeQuotesList,
            PermissionsEnum::HomeQuotesList, PermissionsEnum::PetQuotesList,
        ])) {
            $nav = $nav->add('Personal Quotes', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CarQuotesList),
                        'Car Quotes',
                        '/quotes/car',
                        fn ($s) => $s->attributes(['icon' => 'car'])
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
        }

        if (auth()->user()->hasAnyPermission([
            PermissionsEnum::GMQuotesList,
            PermissionsEnum::CorpLineQuotesList,
        ])) {
            $nav = $nav->add('Business Quotes', '', function (Section $section) {
                $section
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::GMQuotesList),
                        'Group Medical Quotes',
                        '/medical/amt',
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CorpLineQuotesList),
                        'CorpLine Quotes',
                        '/quotes/business',
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

        if (auth()->user()->can(PermissionsEnum::DiscountManagement)) {
            $nav = $nav->add('Discount Management', '', function (Section $section) {
                $section
                    ->add('Base Discount', '/discount/base', fn ($s) => $s->attributes(['icon' => 'box']))
                    ->add('Age Discount', '/discount/age', fn ($s) => $s->attributes(['icon' => 'box']));
            });
        }

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
                        '/transapp/create',
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
                        'Insurance Companies',
                        route('insurancecompany.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'Reasons',
                        route('reason.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'Status',
                        route('status.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::CRMAdmin),
                        'Payment Modes',
                        route('paymentmode.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
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

        if (auth()->user()->hasAnyRole([RolesEnum::Renewals, RolesEnum::RenewalsManager, RolesEnum::Admin, RolesEnum::Engineering])) {
            $nav = $nav->add('Renewals', '', function (Section $section) {
                $section
                    ->add('Upload & Create', url('renewals/upload'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->add('Uploaded Leads', url('renewals/uploaded-leads'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->add('Upload & Update', url('renewals/upload'), fn ($s) => $s->attributes(['icon' => 'box']))
                    ->addIf(
                        auth()->user()->hasAnyRole([RolesEnum::RenewalsManager, RolesEnum::Admin, RolesEnum::Engineering]),
                        'Batches',
                        url('renewals/batches'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    );
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

        if (auth()->check() && auth()->user()->hasPolicyIssuanceAccess()) {
            $nav = $nav->add('Policy Issuance', url('ftcform'));
        }
        $nav = $nav->add('Embedded Products', url('embedded-products'));
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
            PermissionsEnum::InsuranceProviderList,
            PermissionsEnum::ApplicationStorageList,
            PermissionsEnum::RULE_CONFIG_LIST,
            PermissionsEnum::QUAD_CONFIG_LIST,
            PermissionsEnum::TIER_CONFIG_LIST,
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
                        auth()->user()->can(PermissionsEnum::TIER_CONFIG_LIST),
                        'Tiers (Allocation)',
                        url('generic/tier'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::QUAD_CONFIG_LIST),
                        'Quadrants (Allocation)',
                        url('generic/quadrant'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::RULE_CONFIG_LIST),
                        'Rules (Allocation)',
                        url('generic/rule'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::InsuranceProviderList),
                        'Insurance Providers',
                        url('generic/insuranceprovider'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->can(PermissionsEnum::ApplicationStorageList),
                        'Application Storage',
                        url('generic/applicationstorage'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    )
                    ->addIf(
                        auth()->user()->hasRole(RolesEnum::Admin),
                        'Failed Jobs',
                        route('failed-jobs.index'),
                        fn ($s) => $s->attributes(['icon' => 'box'])
                    );
            });
        }

        return $nav;
    }
}
