@php
use App\Enums\RolesEnum;
use App\Enums\PermissionsEnum;
@endphp
<div class="col-md-3 left_col">
    <div class="left_col scroll-view" style="border: 0;backgroundlinear-gradient(0deg,#69d0fe,#4183bd);">
        <div class="navbar nav_title" style="border: 0;background:#eef1f4;">
            <a href="index.html" class="site_title">
                <img src='{{ asset("image/logo.png") }}' style="width:200px;" alt="IMCRM logo" />
            </a>
        </div>
        <div class="clearfix"></div>
        <br />
        <!-- sidebar menu -->
        <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
            <div class="menu_section">

                <ul class="nav side-menu">
                    <li> <a href="{{ url('/leadsearch') }}"><i class="fa fa-home"></i> Home</a></li>
                </ul>
                @can(PermissionsEnum::DashboardView)
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-tachometer"
                        aria-hidden="true"></i>Dashboard <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="{{ url('dashboard/car-conversion') }}">Car Conversion</a></li>
                            <li><a href="{{ url('dashboard/travel-conversion') }}">Travel Conversion</a></li>
                        </ul>
                    </li>
                </ul>
                @endcan
                @can(PermissionsEnum::LeadAllocationView)

                <ul class="nav side-menu">
                    <li><a><i class="fa fa-paper-plane"></i>Lead Allocation<span class="fa fa-chevron-down" style="color: white;"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="{{ url('lead-allocation') }}">Health</a></li>
                            <li><a href="{{ url('generic/quadrant') }}">Car</a></li>
                        </ul>
                    </li>
                </ul>
                @endcan
                @if (auth()->check() && auth()->user()->hasMyLeadAccess())
                <ul class="nav side-menu">
                    <li>
                        <a href="{{ url('/myleads') }}"><i class="fa fa-inbox"></i> My Leads</a>
                    </li>
                </ul>
                @endif
                @can(PermissionsEnum::RewardList)
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-gift"></i> Rewards <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            @can(PermissionsEnum::PartnersList)
                            <li><a href="{{ url('rewards/partner') }}">Partners</a></li>
                            @endcan
                            @can(PermissionsEnum::RewardList)
                            <li><a href="{{ url('rewards/reward') }}">Rewards</a></li>
                            @endcan
                            @can(PermissionsEnum::RewardCategoriesList)
                            <li><a href="{{ url('rewards/reward-categories') }}">Reward Categories</a></li>
                            @endcan
                            @can(PermissionsEnum::RewardTagsList)
                            <li><a href="{{ url('rewards/reward-tags') }}">Reward Tags</a></li>
                            @endcan
                            @can(PermissionsEnum::RewardSliderList)
                            <li><a href="{{ url('rewards/reward-sliders') }}">Reward Slider</a></li>
                            @endcan
                        </ul>
                    </li>
                </ul>
                @endcan
                @can(PermissionsEnum::ActivitiesList)
                <ul class="nav side-menu">
                    <li> <a href="{{ url('/activities') }}"> <i class="fa fa-list-alt" aria-hidden="true"></i>
                            Activities</a></li>
                </ul>
                @endcan
                @canany([PermissionsEnum::CarQuotesList, PermissionsEnum::HealthQuotesList,
                PermissionsEnum::TravelQuotesList, PermissionsEnum::LifeQuotesList,
                PermissionsEnum::HomeQuotesList, PermissionsEnum::PetQuotesList])
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-quote-left"></i> Personal Quotes <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            @can(PermissionsEnum::CarQuotesList)
                            <li><a href="{{ url('quotes/car') }}">Car Quotes</a></li>
                            @endcan
                            @can(PermissionsEnum::HealthQuotesList)
                            <li><a href={{ url('quotes/health') }}>Health Quotes</a></li>
                            @endcan
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
                            <li><a href="{{ url('quotes/pet') }}">Pet Quotes</a></li>
                            @endcan
                        </ul>
                    </li>
                </ul>
                @endcanany
                @canany([PermissionsEnum::GMQuotesList, PermissionsEnum::CorpLineQuotesList])
                <ul class="nav side-menu">
                    <li> <a><i class="fa fa-quote-right"></i> Business Quotes <span
                                class="fa fa-chevron-down"></span></a>
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
                @canany([PermissionsEnum::VehicleDepreciationList, PermissionsEnum::VehicleValuationList])
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-car" aria-hidden="true"></i> Car <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="{{ route('calculatevaluation') }}">Valuation</a></li>
                            <li><a href="{{ url('valuation/vehicledepreciation') }}">Vehicle Depreciation</a></li>
                        </ul>
                    </li>
                </ul>
                @endcanany
                @can(PermissionsEnum::DiscountManagement)
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
                @endcan
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
                @can(PermissionsEnum::RenewalsUpload)
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-quote-left"></i> Renewals <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="{{ url('renewals/upload') }}">Upload & Create</a></li>
                            <li><a href="{{ url('renewals/uploaded-leads') }}">Uploaded Leads</a></li>
                            <li><a href="{{ url('renewals/update') }}">Upload & Update</a></li>
                            <li><a href="{{ url('renewals/batches') }}">Batches</a></li>
                        </ul>
                    </li>
                </ul>
                @endcan
                @can(PermissionsEnum::ClaimList)
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-quote-left"></i> Claims <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="{{ url('claim/claims') }}">Claims</a></li>

                            @can(PermissionsEnum::CRMAdmin)
                            <li><a href="#">Admin <span class="fa fa-chevron-down"></span></a>
                                <ul class="nav child_menu">
                                    @can(PermissionsEnum::TypeOfInsuranceList)
                                    <li><a href="{{ url('claim/typeofinsurance') }}">Type of Insurance</a></li>
                                    @endcan
                                    @can(PermissionsEnum::SubTypeOfInsuranceList)
                                    <li><a href="{{ url('claim/subtypeofinsurance') }}">Sub Type of Insurance</a>
                                    </li>
                                    @endcan
                                    @can(PermissionsEnum::ClaimStatusList)
                                    <li><a href="{{ url('claim/claimsstatus') }}">Claim Status</a></li>
                                    @endcan
                                    @can(PermissionsEnum::CarRepairCoverageList)
                                    <li><a href="{{ url('claim/carrepaircoverage') }}">Car Repair Coverage</a></li>
                                    @endcan
                                    @can(PermissionsEnum::CarRepairTypeList)
                                    <li><a href="{{ url('claim/carrepairtype') }}">Car Repair Type</a></li>
                                    @endcan
                                </ul>
                            </li>
                            @endcan
                        </ul>
                    </li>
                </ul>
                @endcan
                @can(PermissionsEnum::AMLList)
                <ul class="nav side-menu">
                    <li><a><i class="fa fa-desktop"></i> AML <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="{{ url('kyc/aml') }}">All Quotes</a></li>
                            <li><a href="{{ url('kyc/aml/download/history') }}">Downloaded Sanction Lists</a></li>
                            <li><a href="{{ url('kyc/aml/upload/uae') }}">Upload UAE List</a></li>
                        </ul>
                    </li>
                </ul>
                @endcan
                @if (auth()->check() && auth()->user()->hasPolicyIssuanceAccess())
                <ul class="nav side-menu">
                    <li><a href="{{ url('ftcform') }}"><i></i> Policy Issuance </a>
                </ul>
                @endif
                @if (auth()->check() && auth()->user()->isAdmin())
                <ul class="nav side-menu">
                    <li><a href="{{ url('assignOE') }}"><i></i> Assign OE </a>
                </ul>
                @endif
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
                PermissionsEnum::InsuranceProviderList, PermissionsEnum::ApplicationStorageList])
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
                            <li><a href="{{ url('generic/teams') }}">Teams</a></li>
                            @endcan
                            <li><a>Allocation Config<span class="fa fa-chevron-down" style="color: white;"></span></a>
                                <ul class="nav child_menu">
                                    <li><a href="{{ url('generic/tier') }}">Tiers</a></li>
                                    <li><a href="{{ url('generic/quadrant') }}">Quadrants</a></li>
                                </ul>
                            </li>
                            @can(PermissionsEnum::InsuranceProviderList)
                            <li><a href="{{ url('generic/insuranceprovider') }}">Insurance Providers</a></li>
                            @endcan
                            @can(PermissionsEnum::ApplicationStorageList)
                            <li><a href="{{ url('generic/applicationstorage') }}">Application Storage</a></li>
                            @endcan
                        </ul>
                    </li>
                </ul>
                @endcanany
            </div>
        </div>
        <!-- /sidebar menu -->
    </div>
</div>
