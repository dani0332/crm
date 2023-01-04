<?php

namespace App\Providers;

use App\Enums\EnvEnum;
use App\Jobs\LeadAllocationJob;
use App\Services\LeadAllocationService;
use Barryvdh\Debugbar\Facades\Debugbar;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(LeadAllocationJob::class, function ($app) {
            return new LeadAllocationService($app->make(LeadAllocationService::class));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Filament::serving(function () {
            Filament::registerTheme(
                mix('css/filament.css'),
            );

            Filament::registerNavigationGroups([
                NavigationGroup::make()->label('Dashboard'),
                NavigationGroup::make()->label('Reports'),
                NavigationGroup::make()->label('My Leads'),
                NavigationGroup::make()->label('Rewards'),
                NavigationGroup::make()->label('Activities'),
                NavigationGroup::make()->label('Personal Quotes'),
                NavigationGroup::make()->label('Business Quotes'),
                NavigationGroup::make()->label('Car'),
                NavigationGroup::make()->label('Discount Management'),
                NavigationGroup::make()->label('Trans App'),
                NavigationGroup::make()->label('Customers'),
                NavigationGroup::make()->label('Renewals'),
                NavigationGroup::make()->label('Claims'),
                NavigationGroup::make()->label('AML'),
                NavigationGroup::make()->label('Telemarketing'),
                NavigationGroup::make()->label('Admin'),
            ]);
        });

        Filament::serving(function () {
            $nav_items = [];

            array_push(
                $nav_items,
                NavigationItem::make('Car Conversion')
                    ->url('/dashboard/car-conversion')
                    ->icon('heroicon-o-chart-pie')
                    ->group('Dashboard'),
                NavigationItem::make('Travel Conversion')
                    ->url('/dashboard/travel-conversion')
                    ->icon('heroicon-o-chart-pie')
                    ->group('Dashboard'),
                NavigationItem::make('TPL Conversion')
                    ->url('/tpl-conversion-dashboard')
                    ->icon('heroicon-o-chart-pie')
                    ->group('Dashboard'),
                NavigationItem::make('Comprehensive Conversion')
                    ->url('/comprehensive-conversion-dashboard')
                    ->icon('heroicon-o-chart-pie')
                    ->group('Dashboard'),
                NavigationItem::make('Accumulative Dashboard')
                    ->url('/accumulative-dashboard')
                    ->icon('heroicon-o-chart-pie')
                    ->group('Dashboard'),
                NavigationItem::make('Advisor Conversion')
                    ->url('/reports/advisor-conversion')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->group('Reports'),
                NavigationItem::make('Advisor Performance')
                    ->url('/reports/advisor-performance')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->group('Reports'),
                NavigationItem::make('Advisor Distribution')
                    ->url('/reports/advisor-distribution')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->group('Reports'),
                NavigationItem::make('Lead Distribution')
                    ->url('/reports/lead-distribution')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->group('Reports'),
                NavigationItem::make('Partners')
                    ->url('/rewards/partner')
                    ->icon('heroicon-o-gift')
                    ->group('Rewards'),
                NavigationItem::make('Rewards')
                    ->url('/rewards/reward')
                    ->icon('heroicon-o-gift')
                    ->group('Rewards'),
                NavigationItem::make('Reward Categories')
                    ->url('/rewards/reward-categories')
                    ->icon('heroicon-o-gift')
                    ->group('Rewards'),
                NavigationItem::make('Reward Tags')
                    ->url('/rewards/reward-tags')
                    ->icon('heroicon-o-gift')
                    ->group('Rewards'),
                NavigationItem::make('Reward Slider')
                    ->url('/rewards/reward-sliders')
                    ->icon('heroicon-o-gift')
                    ->group('Rewards'),
                NavigationItem::make('Car Quotes')
                    ->url('/quotes/car')
                    ->icon('heroicon-o-star')
                    ->group('Personal Quotes'),
                NavigationItem::make('Travel Quotes')
                    ->url('/quotes/travel')
                    ->icon('heroicon-o-star')
                    ->group('Personal Quotes')->sort(3),
                NavigationItem::make('Life Quotes')
                    ->url('/quotes/life')
                    ->icon('heroicon-o-star')
                    ->group('Personal Quotes')->sort(3),
                NavigationItem::make('Home Quotes')
                    ->url('/quotes/home')
                    ->icon('heroicon-o-star')
                    ->group('Personal Quotes')->sort(3),
                NavigationItem::make('Pet Quotes')
                    ->url('/quotes/pet')
                    ->icon('heroicon-o-star')
                    ->group('Personal Quotes')->sort(3),
                NavigationItem::make('Group Medical Quotes')
                    ->url('/medical/amt')
                    ->icon('heroicon-o-star')
                    ->group('Business Quotes'),
                NavigationItem::make('CorpLine Quotes')
                    ->url('/quotes/business')
                    ->icon('heroicon-o-star')
                    ->group('Business Quotes'),
                NavigationItem::make('Valuation')
                    ->url('/calculatevaluation')
                    ->icon('heroicon-o-calculator')
                    ->group('Car'),
                NavigationItem::make('Vehicle Depreciation')
                    ->url('/valuation/vehicledepreciation')
                    ->icon('heroicon-o-calculator')
                    ->group('Car'),
                NavigationItem::make('Base Discount')
                    ->url('/discount/base')
                    ->icon('heroicon-o-cube')
                    ->group('Discount Management'),
                NavigationItem::make('Age Discount')
                    ->url('/discount/age')
                    ->icon('heroicon-o-cube')
                    ->group('Discount Management'),
                NavigationItem::make('Search Transaction')
                    ->url('/transapp/home')
                    ->icon('heroicon-o-cube')
                    ->group('Trans App'),
                NavigationItem::make('Create Transaction')
                    ->url('/transapp/transaction/create')
                    ->icon('heroicon-o-cube')
                    ->group('Trans App'),
                NavigationItem::make('Cancel & Re-Issue Transaction')
                    ->url('/transapp/re-issue-transaction')
                    ->icon('heroicon-o-cube')
                    ->group('Trans App'),
                NavigationItem::make('Cancel Transaction (without Re-Issue)')
                    ->url('/transapp/cancel-transaction')
                    ->icon('heroicon-o-cube')
                    ->group('Trans App'),
                NavigationItem::make('Transaction List')
                    ->url('/transapp/transaction')
                    ->icon('heroicon-o-cube')
                    ->group('Trans App'),
                NavigationItem::make('Insurance Companies')
                    ->url('/transapp/insurancecompany')
                    ->icon('heroicon-o-cube')
                    ->group('Trans App'),
                NavigationItem::make('Reason')
                    ->url('/transapp/reason')
                    ->icon('heroicon-o-cube')
                    ->group('Trans App'),
                NavigationItem::make('Status')
                    ->url('/transapp/status')
                    ->icon('heroicon-o-cube')
                    ->group('Trans App'),
                NavigationItem::make('Payment Mode')
                    ->url('/transapp/paymentmode')
                    ->icon('heroicon-o-cube')
                    ->group('Trans App'),
                NavigationItem::make('Search')
                    ->url('/customer')
                    ->icon('heroicon-o-user-group')
                    ->group('Customers'),
                NavigationItem::make('Upload')
                    ->url('/customer-upload')
                    ->icon('heroicon-o-user-group')
                    ->group('Customers'),
                NavigationItem::make('Upload & Create')
                    ->url('/renewals/upload')
                    ->icon('heroicon-o-cube')
                    ->group('Renewals'),
                NavigationItem::make('Uploaded Leads')
                    ->url('/renewals/uploaded-leads')
                    ->icon('heroicon-o-cube')
                    ->group('Renewals'),
                NavigationItem::make('Upload & Update')
                    ->url('/renewals/update')
                    ->icon('heroicon-o-cube')
                    ->group('Renewals'),
                NavigationItem::make('Batches')
                    ->url('/renewals/batches')
                    ->icon('heroicon-o-cube')
                    ->group('Renewals'),
                NavigationItem::make('Claims')
                    ->url('/claim/claims')
                    ->icon('heroicon-o-cube')
                    ->group('Claims'),
                NavigationItem::make('Type of Insurance')
                    ->url('/claim/typeofinsurance')
                    ->icon('heroicon-o-cube')
                    ->group('Claims'),
                NavigationItem::make('Sub Type of Insurance')
                    ->url('/claim/subtypeofinsurance')
                    ->icon('heroicon-o-cube')
                    ->group('Claims'),
                NavigationItem::make('Claim Status')
                    ->url('/claim/claimsstatus')
                    ->icon('heroicon-o-cube')
                    ->group('Claims'),
                NavigationItem::make('Car Repair Coverage')
                    ->url('/claim/carrepaircoverage')
                    ->icon('heroicon-o-cube')
                    ->group('Claims'),
                NavigationItem::make('Car Repair Type')
                    ->url('/claim/carrepairtype')
                    ->icon('heroicon-o-cube')
                    ->group('Claims'),
                NavigationItem::make('All Quotes')
                    ->url('/kyc/aml')
                    ->icon('heroicon-o-cube')
                    ->group('AML'),
                NavigationItem::make('Downloaded Sanction Lists')
                    ->url('/kyc/aml/download/history')
                    ->icon('heroicon-o-cube')
                    ->group('AML'),
                NavigationItem::make('Upload UAE List')
                    ->url('/kyc/aml/upload/uae')
                    ->icon('heroicon-o-cube')
                    ->group('AML'),
                NavigationItem::make('TM Leads')
                    ->url('/telemarketing/tmleads')
                    ->icon('heroicon-o-cube')
                    ->group('Telemarketing'),
                NavigationItem::make('Upload TM Leads')
                    ->url('/telemarketing/tmuploadlead')
                    ->icon('heroicon-o-cube')
                    ->group('Telemarketing'),
                NavigationItem::make('TM Type of Insurance')
                    ->url('/telemarketing/tminsurancetype')
                    ->icon('heroicon-o-cube')
                    ->group('Telemarketing'),
                NavigationItem::make('TM Lead Status')
                    ->url('/telemarketing/tmleadstatus')
                    ->icon('heroicon-o-cube')
                    ->group('Telemarketing'),
                NavigationItem::make('Teams')
                    ->url('/generic/teams')
                    ->icon('heroicon-o-cube')
                    ->group('Admin')->sort(3),
                NavigationItem::make('Tiers')
                    ->url('/generic/tier')
                    ->icon('heroicon-o-cube')
                    ->group('Admin')->sort(3),
                NavigationItem::make('Quadrants')
                    ->url('/generic/quadrant')
                    ->icon('heroicon-o-cube')
                    ->group('Admin')->sort(3),
                NavigationItem::make('Rules')
                    ->url('/generic/rule')
                    ->icon('heroicon-o-cube')
                    ->group('Admin')->sort(3),
                NavigationItem::make('Insurance Providers')
                    ->url('/generic/insuranceprovider')
                    ->icon('heroicon-o-cube')
                    ->group('Admin')->sort(3),
                NavigationItem::make('Application Storage')
                    ->url('/generic/applicationstorage')
                    ->icon('heroicon-o-cube')
                    ->group('Admin')->sort(3),
                NavigationItem::make('Failed Jobs')
                    ->url('/failed-jobs')
                    ->icon('heroicon-o-cube')
                    ->group('Admin')->sort(3),
            );

            if (auth()->check() && auth()->user()->hasMyLeadAccess()) {
                array_push(
                    $nav_items,
                    NavigationItem::make('My Leads')
                        ->url('/myleads')
                        ->icon('heroicon-o-user-group')
                        ->group('My Leads'),
                );
            }
            Filament::registerNavigationItems($nav_items);
        });
        $allowedEnvs = [EnvEnum::LOCAL, EnvEnum::DEVELOPMENT, EnvEnum::STAGING];
        if (in_array(config('APP_ENV', 'production'), $allowedEnvs)) {
            Debugbar::enable();
        }
        // DB::listen(function($query) {
        //     Log::info(
        //         $query->sql,
        //         $query->bindings,
        //         $query->time
        //     );
        // });
    }
}
