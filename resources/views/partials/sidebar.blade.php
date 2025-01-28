@php
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Enums\PermissionsEnum;
@endphp
<div class="col-md-3 left_col">
    <div class="left_col scroll-view" style="border: 0;backgroundlinear-gradient(0deg,#69d0fe,#4183bd);">
        <div class="navbar nav_title" style="border: 0;background:#eef1f4;">
            <a href="/" class="site_title">
                <img src='{{ asset("image/new_logo.png") }}' alt="IMCRM logo" class="sidebar-expand-logo" />
                <img src='{{ asset("image/alfred-theme.png") }}' alt="IMCRM logo" class="sidebar-collapse-logo d-none" />
            </a>
        </div>
        <div class="clearfix"></div>
        <br />
        <!-- sidebar menu -->
        <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
            <div class="menu_section">

                <ul class="nav side-menu">
                    <li> <a href="{{ url('/home') }}"><i class="fa fa-home"></i> Home</a></li>
                </ul>
                @canany([PermissionsEnum::DashboardView, PermissionsEnum::TPL_DASHBOARD_VIEW, PermissionsEnum::COMPREHENSIVE_DASHBOARD_VIEW, PermissionsEnum::MAIN_DASHBOARD_VIEW, PermissionsEnum::UtmLeadsSalesReport])
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-tachometer" aria-hidden="true"></i>Dashboard <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            @can(PermissionsEnum::DashboardView)
                            <li><a href="{{ url('dashboard/car-conversion') }}">Car Conversion</a></li>
                            <li><a href="{{ url('dashboard/travel-conversion') }}">Travel Conversion</a></li>
                            @endcan
                            @can(PermissionsEnum::COMPREHENSIVE_DASHBOARD_VIEW)
                            <li><a href="{{ url('/comprehensive-conversion-dashboard') }}">Comprehensive Conversion</a></li>
                            @endcan
                            @can(PermissionsEnum::MAIN_DASHBOARD_VIEW)
                            <li><a href="{{ url('/accumulative-dashboard') }}">Accumulative Dashboard</a></li>
                            @endcan
                        </ul>
                    </li>
                </ul>
                @endcanany
                @canany([PermissionsEnum::ADVISOR_CONVERSION_REPORT_VIEW, PermissionsEnum::ADVISOR_PERFORMANCE_REPORT_VIEW, PermissionsEnum::ADVISOR_DISTRIBUTION_REPORT_VIEW, PermissionsEnum::LEAD_DISTRIBUTION_REPORT_VIEW,PermissionsEnum::UtmLeadsSalesReport, PermissionsEnum::RENEWAL_BATCH_REPORT, PermissionsEnum::REVIVAL_CONVERSION_REPORT_VIEW])
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-line-chart" aria-hidden="true"></i>Reports <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            @can(PermissionsEnum::ADVISOR_CONVERSION_REPORT_VIEW)
                            <li><a href="{{ url('reports/advisor-conversion') }}">Advisor Conversion</a></li>
                            @endif
                            @can(PermissionsEnum::ADVISOR_PERFORMANCE_REPORT_VIEW)
                            <li><a href="{{ url('reports/advisor-performance') }}">Advisor Performance</a></li>
                            @endcan
                            @can(PermissionsEnum::ADVISOR_DISTRIBUTION_REPORT_VIEW)
                            <li><a href="{{ url('reports/advisor-distribution') }}">Advisor Distribution</a></li>
                            @endcan
                            @can(PermissionsEnum::LEAD_DISTRIBUTION_REPORT_VIEW)
                            <li><a href="{{ url('reports/lead-distribution') }}">Lead Distribution</a></li>
                            @can(PermissionsEnum::REVIVAL_CONVERSION_REPORT_VIEW)
                            <li><a href="{{ url('reports/revival-conversion') }}">Revival Conversion</a></li>
                            @endcan
                            @endcan
                            @can(PermissionsEnum::UtmLeadsSalesReport)
                                    <li><a href="{{ url('reports/utm-report') }}">UTM Report</a></li>
                            @endcan
                            @can(PermissionsEnum::TOTAL_PREMIUM_LEADS_SALES_REPORT)
                                    <li><a href="{{ url('/reports/total-premium') }}">Total Premium Report</a></li>
                            @endcan
                            @can(PermissionsEnum::RENEWAL_BATCH_REPORT)
                                <li><a href="{{ url('reports/renewal-report') }}">Daily Renewal Report</a></li>
                            @endcan
                        </ul>
                    </li>
                </ul>
                @endcanany
                @canany([PermissionsEnum::CAR_LEAD_ALLOCATION_DASHBOARD, PermissionsEnum::HEALTH_LEAD_ALLOCATION_DASHBOARD,PermissionsEnum::UtmLeadsSalesReport])
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-paper-plane"></i>Lead Allocation<span class="fa fa-chevron-down" style="color: white;"></span></a>
                        <ul class="nav child_menu">
                            @can(PermissionsEnum::HEALTH_LEAD_ALLOCATION_DASHBOARD)
                            <li><a href="{{ url('lead-allocation') }}">Health</a></li>
                            @endcan
                            @can(PermissionsEnum::CAR_LEAD_ALLOCATION_DASHBOARD)
                            <li><a href="{{ url('car-lead-allocation') }}">Car</a></li>
                            @endcan

                        </ul>
                    </li>
                </ul>
                @endcanany
                @can(PermissionsEnum::ActivitiesList)
                <ul class="nav side-menu">
                    <li> <a href="{{ url('/activities') }}"> <i class="fa fa-list-alt" aria-hidden="true"></i>
                            Activities</a></li>
                </ul>
                @endcan
                @canany([
                PermissionsEnum::CarQuotesList,
                PermissionsEnum::HealthQuotesList,
                PermissionsEnum::TravelQuotesList,
                PermissionsEnum::LifeQuotesList,
                PermissionsEnum::HomeQuotesList,
                PermissionsEnum::PetQuotesList,
                PermissionsEnum::BikeQuotesList,
                PermissionsEnum::CycleQuotesList,
                PermissionsEnum::YachtQuotesList,
                PermissionsEnum::JetskiQuotesList,
                PermissionsEnum::UtmLeadsSalesReport,
                PermissionsEnum::CAR_REVIVAL_QUOTE_LIST,
                PermissionsEnum::HEALTH_REVIVAL_QUOTES_LIST
                ])
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-quote-left"></i> Personal Quotes <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            @canany([PermissionsEnum::CarQuotesList, PermissionsEnum::CarQuoteSearch,PermissionsEnum::CAR_REVIVAL_QUOTE_LIST])
                            <li><a>Car<span class="fa fa-chevron-down" style="color: white;"></span></a>
                                <ul class="nav child_menu">
                                    @can(PermissionsEnum::CarQuoteSearch)
                                    <li><a href="{{ url('/personal-quotes/car/car-quotes-search') }}">Search</a></li>
                                    @endcan
                                    <li><a href="{{ url('quotes/car') }}">Lead List</a></li>

                                    @can(PermissionsEnum::CAR_REVIVAL_QUOTE_LIST)
                                    <li><a href="{{ route('carrevival-quotes-list') }}">Revival Quotes</a></li>
                                    @endcan
                                </ul>
                            </li>
                            @endcanany

                            <li><a>Health<span class="fa fa-chevron-down" style="color: white;"></span></a>
                                <ul class="nav child_menu">
                                    @can(PermissionsEnum::HealthQuotesList)
                                    <li><a href={{ url('quotes/health') }}>Health Quotes</a></li>
                                    @endcan

                                    @can(PermissionsEnum::HEALTH_REVIVAL_QUOTES_LIST)
                                    <li><a href={{ url('quotes/health-revival') }}>Health Revival Quotes</a></li>
                                    @endcan
                                </ul>
                            </li>
                            @can(PermissionsEnum::TravelQuotesList)
                            <li><a href="{{ url('quotes/travel') }}">Travel Quotes</a></li>
                            @endcan
                            @can(PermissionsEnum::LifeQuotesList)
                            <li><a href="{{ url('quotes/life') }}">Life Quotes</a></li>
                            @endcan
                            @can(PermissionsEnum::HomeQuotesList)
                            <li><a href="{{ url('quotes/home') }}">Home Quotes</a></li>
                            @endcan
                            @can(PermissionsEnum::PetQuotesList)
                            <li><a href="{{ url('personal-quotes/pet') }}">Pet Quotes</a></li>
                            @endcan
                            @can(PermissionsEnum::BikeQuotesList)
                            <li><a href="{{ url('personal-quotes/bike') }}">Bike Quotes</a></li>
                            @endcan
                            @can(PermissionsEnum::CycleQuotesList)
                            <li><a href="{{ url('personal-quotes/cycle') }}">Cycle Quotes</a></li>
                            @endcan
                            @can(PermissionsEnum::YachtQuotesList)
                            <li><a href="{{ url('personal-quotes/yacht') }}">Yacht Quotes</a></li>
                            @endcan
                            @can(PermissionsEnum::JetskiQuotesList)
                            <li><a href="{{ url('personal-quotes/jetski') }}">JetSki Quotes</a></li>
                            @endcan
                        </ul>
                    </li>
                </ul>
                @endcanany
                @canany([PermissionsEnum::GMQuotesList, PermissionsEnum::CorpLineQuotesList])
                <ul class="nav side-menu">
                    <li> <a><i class="fa fa-quote-right"></i> Business Quotes <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            @can(PermissionsEnum::GMQuotesList)
                            <li><a href="{{ url('medical/amt') }}"> Group Medical Quotes </a></li>
                            @endcan
                            @can(PermissionsEnum::CorpLineQuotesList)
                            <li><a href="{{ url('quotes/business') }}"> CorpLine Quotes </a></li>
                            @endcan
                        </ul>
                </ul>
                @endcanany
                @canany([PermissionsEnum::CAR_SOLD_LIST, PermissionsEnum::CAR_UNCONTACTABLE_LIST])
                    <ul class="nav side-menu">
                        <li> <a><i class="fa fa-quote-right"></i>Car Sold / Uncon <span class="fa fa-chevron-down"></span></a>
                            <ul class="nav child_menu">
                                @can(PermissionsEnum::CAR_SOLD_LIST)
                                    <li><a href="{{ url('/quotes/car-sold') }}"> Car Sold </a></li>
                                @endcan
                                @can(PermissionsEnum::CAR_UNCONTACTABLE_LIST)
                                    <li><a href="{{ url('/quotes/car-uncontactable') }}"> Car Uncontactable </a></li>
                                @endcan
                            </ul>
                    </ul>
                @endcanany

                @canany([PermissionsEnum::VehicleDepreciationList, PermissionsEnum::VehicleValuationList])
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-car" aria-hidden="true"></i> Car <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="{{ route('valuation') }}">Valuation</a></li>
                            <li><a href="{{ url('valuation/vehicledepreciation') }}">Vehicle Depreciation</a></li>
                        </ul>
                    </li>
                </ul>
                @endcanany
                <!-- @can(PermissionsEnum::DiscountManagement)
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-strikethrough"></i> Discount Management <span class="fa fa-chevron-down"></a>
                        <ul class="nav child_menu">
                            @can(PermissionsEnum::DiscountList)
                            <li><a href="{{ url('discount/base') }}">Base Discount </a></li>
                            @endcan
                            @can(PermissionsEnum::DiscountList)
                            <li><a href="{{ url('discount/age') }}">Age Discount </a></li>
                            @endcan
                        </ul>
                    </li>
                </ul>
                @endcan -->
                @can(PermissionsEnum::TransAppList)
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-desktop"></i> Trans App <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            @can(PermissionsEnum::TransAppCreate)
                            <li><a href="{{ route('home') }}">Search Transaction</a></li>
                            @endcan
                            @can(PermissionsEnum::TransAppCreate)
                            <li><a href="{{ route('transaction.create') }}">Create Transaction</a></li>
                            @endcan
                            @can(PermissionsEnum::TransAppEdit)
                            <li class="sub_menu"><a href="{{ route('reissue_view') }}">Cancel & Re-Issue
                                    Transaction</a></li>
                            <li class="sub_menu"><a href="{{ route('cancel_view') }}">Cancel Transaction
                                    (without Re-Issue)</a></li>
                            @endcan
                            <li><a href="{{ route('transaction.index') }}">Transaction List</a></li>

                            @can(PermissionsEnum::CRMAdmin)
                            <li><a href="#">Admin <span class="fa fa-chevron-down"></span></a>
                                <ul class="nav child_menu">
                                    @can(PermissionsEnum::InsuranceCompanyList)
                                    <li><a href="{{ route('insurancecompany.index') }}">Insurance Companies</a></li>
                                    @endcan
                                    @can(PermissionsEnum::ReasonList)
                                    <li><a href="{{ route('reason.index') }}">Reasons</a></li>
                                    @endcan
                                    @can(PermissionsEnum::StatusList)
                                    <li><a href="{{ route('status.index') }}">Status</a></li>
                                    @endcan
                                    @can(PermissionsEnum::PaymentModeList)
                                    <li><a href="{{ route('paymentmode.index') }}">Payment Modes</a></li>
                                    @endcan
                                </ul>
                            </li>
                            @endcan
                        </ul>
                    </li>
                </ul>
                @endcan
                @can(PermissionsEnum::CustomersList)
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-user"></i> Customers <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="{{ url('customer') }}">Search</a></li>
                            @can('customers-upload')
                            <li><a href="{{ url('customer-upload') }}">Upload</a></li>
                            @endcan
                        </ul>
                    </li>
                </ul>
                @endcan
                @canany([PermissionsEnum::RenewalsUpload, PermissionsEnum::RenewalsUploadedLeadList, PermissionsEnum::RenewalsUploadUpdate, PermissionsEnum::RenewalsBatches])
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-quote-left"></i> Renewals <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            @can(PermissionsEnum::RenewalsUpload)
                            <li><a href="{{ route('renewals-upload-create') }}">Upload & Create</a></li>
                            @endcan
                            @can(PermissionsEnum::RenewalsUploadedLeadList)
                            <li><a href="{{ route('renewals-uploaded-leads-list') }}">Uploaded Leads</a></li>
                            @endcan
                            @can(PermissionsEnum::RenewalsUploadUpdate)
                            <li><a href="{{ route('renewals-upload-update') }}">Upload & Update</a></li>
                            @endcan
                            @can(PermissionsEnum::RenewalsBatches)
                            <li><a href="{{ route('renewals-batches') }}">Batches</a></li>
                            @endcan
                              <li><a href="{{ route('renewals-batches-search') }}">Search</a></li>
                        </ul>
                    </li>
                </ul>
                @endcanany
                @can(PermissionsEnum::AMLList)
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-desktop"></i> AML <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="{{ url('kyc/aml') }}">All Quotes</a></li>
                        </ul>
                    </li>
                </ul>
                @endcan
                @can(PermissionsEnum::EMBEDDED_PRODUCT_CONFIG)
                <ul class="nav side-menu">
                    <li><a href="{{ url('embedded-products') }}"><i></i> Embedded Products </a>
                </ul>
                @endcan

                <ul class="nav side-menu">
                    @can(PermissionsEnum::VIEW_LEGACY_DETAILS)
                    <li><a href="{{ url('legacy-policy') }}"><i></i>Legacy Policies</a>
                    @endcan
                </ul>

                {{-- @if (auth()->check() && auth()->user()->isAdmin())
                <ul class="nav side-menu">
                    <li><a href="{{ url('assignOE') }}"><i></i> Assign OE </a>
                </ul>
                @endif --}}
                @can(PermissionsEnum::TeleMarketingList)
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-quote-left"></i> Telemarketing <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="{{ url('telemarketing/tmleads') }}">TM Leads</a></li>
                            @can('tm-upload-leads-list')
                            <li><a href="{{ url('telemarketing/tmuploadlead') }}">Upload TM Leads</a></li>
                            @endcan
                            @can(PermissionsEnum::CRMAdmin)
                            <li><a href="#">Admin <span class="fa fa-chevron-down"></span></a>
                                <ul class="nav child_menu">
                                    @can(PermissionsEnum::TMInsuranceTypeList)
                                    <li><a href="{{ url('telemarketing/tminsurancetype') }}">TM Type of Insurance</a>
                                    </li>
                                    @endcan
                                    @can(PermissionsEnum::TMLeadStatusList)
                                    <li><a href="{{ url('telemarketing/tmleadstatus') }}">TM Lead Status</a></li>
                                    @endcan
                                </ul>
                            </li>
                            @endcan
                        </ul>
                    </li>
                </ul>
                @endcan
                @canany([PermissionsEnum::UsersList, PermissionsEnum::RoleList, PermissionsEnum::TeamsList,
                PermissionsEnum::InsuranceProviderList, PermissionsEnum::ApplicationStorageList,PermissionsEnum::RULE_CONFIG_LIST, PermissionsEnum::QUAD_CONFIG_LIST , PermissionsEnum::TIER_CONFIG_LIST, PermissionsEnum::TeamThresholdView,
                PermissionsEnum::COMMERCIAL_KEYWORDS, PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES,
                PermissionsEnum::RENEWAL_BATCHES_LIST])
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-user"></i> Admin <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            @can(PermissionsEnum::UsersList)
                            <li><a href="{{ url('admin/users') }}">Users</a></li>
                            @endcan
                            @can(PermissionsEnum::RoleList)
                            <li><a href="{{ url('admin/roles') }}">Roles</a></li>
                            @endcan
                            @can(PermissionsEnum::TeamsList)
                            <li><a href="{{ url('generic/team') }}">Teams</a></li>
                            @endcan
                            @can(PermissionsEnum::RENEWAL_BATCHES_LIST)
                            <li><a href="{{ route('renewal-batches-list') }}">Renewal Batches</a></li>
                            @endcan
                            @canany([PermissionsEnum::RULE_CONFIG_LIST, PermissionsEnum::QUAD_CONFIG_LIST , PermissionsEnum::TIER_CONFIG_LIST, PermissionsEnum::TeamThresholdView,
                            PermissionsEnum::COMMERCIAL_KEYWORDS, PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES])
                            <li><a>Allocation Config<span class="fa fa-chevron-down" style="color: white;"></span></a>
                                <ul class="nav child_menu">
                                    @can(PermissionsEnum::TIER_CONFIG_LIST)
                                    <li><a href="{{ url('generic/tier') }}">Tiers</a></li>
                                    @endcan
                                    @can(PermissionsEnum::QUAD_CONFIG_LIST)
                                    <li><a href="{{ url('generic/quadrant') }}">Quadrants</a></li>
                                    @endcan
                                    @can(PermissionsEnum::RULE_CONFIG_LIST)
                                    <li><a href="{{ url('generic/rule') }}">Rules</a></li>
                                    @endcan
                                    @can(PermissionsEnum::TeamThresholdView)
                                        <li><a href="{{ url('generic/allocation-threshold') }}">Team Threshold </a></li>
                                    @endcan
                                    @can(PermissionsEnum::COMMERCIAL_KEYWORDS)
                                        <li><a href="{{ route('admin.commercial.keywords') }}">Commerical Keywords</a></li>
                                    @endcan
                                    @can(PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES)
                                        <li><a href="{{ route('admin.configure.commerical.vehicles') }}">Configure Commercial Vehicles</a></li>
                                    @endcan
                                </ul>
                            </li>
                            @endcanany
                        </ul>
                    </li>
                </ul>
                @endcanany
            </div>
        </div>
        <!-- /sidebar menu -->
    </div>
</div>
