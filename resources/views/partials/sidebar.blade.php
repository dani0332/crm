<div class="col-md-3 left_col">
    <div class="left_col scroll-view" style="border: 0;backgroundlinear-gradient(0deg,#69d0fe,#4183bd);">
        <div class="navbar nav_title" style="border: 0;background:#eef1f4;">
            <a href="index.html" class="site_title">
                <img src='{{ asset('image/logo.png') }}' style="width:200px;" alt="IMCRM logo" />
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
                @can('crm-admin')
                    <ul class="nav side-menu">
                        <li> <a><i class="fa fa-dashboard"></i> Dashboard <span class="fa fa-chevron-down"></span></a>
                            <ul class="nav child_menu">
                                <li><a href="{{ url('dashboard') }}">Over All Dashboard</a></li>
                                @hasrole('MANAGER')
                                    <li><a href="{{ url('/leadassignment') }}">Lead Assignment</a></li>
                                @endhasrole

                                @if (Auth::user()->hasAnyRole(['MANAGER','CAR_ADVISOR', 'BUSINESS_ADVISOR', 'HEALTH_ADVISOR','HOME_ADVISOR','LIFE_ADVISOR','TRAVEL_ADVISOR']))
                                    <li><a href="{{ url('/myleads') }}">My Leads</a></li>
                                @endif
                            </ul>
                    </ul>
                @endcan
                @can('rewards-list')
                    <ul class="nav side-menu">
                        <li><a><i class="fa fa-gift"></i> Rewards <span class="fa fa-chevron-down"></span></a>
                            <ul class="nav child_menu">
                                @can('partners-list')
                                    <li><a href="{{ url('rewards/partner') }}">Partners</a></li>
                                @endcan
                                @can('rewards-list')
                                    <li><a href="{{ url('rewards/reward') }}">Rewards</a></li>
                                @endcan
                                @can('reward-categories-list')
                                    <li><a href="{{ url('rewards/reward-categories') }}">Reward Categories</a></li>
                                @endcan
                                @can('reward-tags-list')
                                    <li><a href="{{ url('rewards/reward-tags') }}">Reward Tags</a></li>
                                @endcan
                            </ul>
                        </li>
                    </ul>
                @endcan
                @can('personal-quotes')
                    <ul class="nav side-menu">
                        <li><a><i class="fa fa-quote-left"></i> Personal Quotes <span class="fa fa-chevron-down"></span></a>
                            <ul class="nav child_menu">
                                {{-- @can('car-quotes-list')
                                    <li><a href="{{ url('quotes/carquotes') }}">Car Quotes</a></li>
                                @endcan --}}
                                @can('car-quotes-list')
                                    <li><a href="{{ url('quotes/car') }}">Car Quotes</a></li>
                                @endcan
                                @can('health-quotes-list')
                                    <li><a href="{{ url('quotes/health') }}">Health Quotes</a></li>
                                @endcan
                                @can('travel-quotes-list')
                                    <li><a href="{{ url('quotes/travel') }}">Travel Quotes</a></li>
                                @endcan
                                @can('life-quotes-list')
                                    <li><a href="{{ url('quotes/life') }}">Life Quotes</a></li>
                                @endcan
                                @can('home-quotes-list')
                                    <li><a href="{{ url('quotes/home') }}">Home Quotes</a></li>
                                @endcan
                                @can('business-quotes-list')
                                    <li><a href="{{ url('quotes/business') }}">Business Quotes</a></li>
                                @endcan
                            </ul>
                        </li>
                    </ul>
                @endcan
                @canany(['vehicle-depreciation-list', 'vehicle-valuation-list'])
                    <ul class="nav side-menu">
                        <li><a><i class="fa fa-car" aria-hidden="true"></i> Car <span
                                    class="fa fa-chevron-down"></span></a>
                            <ul class="nav child_menu">
                                <li><a href="{{ route('calculatevaluation') }}">Valuation</a></li>
                                <li><a href="{{ url('valuation/vehicledepreciation') }}">Vehicle Depreciation</a></li>
                            </ul>
                        </li>
                    </ul>
                @endcanany
                @can('discount-management')
                    <ul class="nav side-menu">
                        <li><a><i class="fa fa-strikethrough"></i> Discount Management <span class="fa fa-chevron-down"></a>
                            <ul class="nav child_menu">
                                @can('discount-list')
                                    <li><a href="{{ url('discount/base') }}">Base Discount </a></li>
                                @endcan
                                @can('discount-list')
                                    <li><a href="{{ url('discount/age') }}">Age Discount </a></li>
                                @endcan
                            </ul>
                        </li>
                    </ul>
                @endcan
                @can('transapp-list')
                    <ul class="nav side-menu">
                        <li><a><i class="fa fa-desktop"></i> Trans App <span class="fa fa-chevron-down"></span></a>
                            <ul class="nav child_menu">
                                @can('transapp-create')
                                    <li><a href="{{ route('home') }}">Search Transaction</a></li>
                                @endcan
                                @can('transapp-edit')
                                    <li class="sub_menu"><a href="{{ route('reissue_view') }}">Cancel & Re-Issue
                                            Transaction</a></li>
                                    <li class="sub_menu"><a href="{{ route('cancel_view') }}">Cancel Transaction
                                            (without Re-Issue)</a></li>
                                @endcan
                                <li><a href="{{ route('transaction.index') }}">Transaction List</a></li>

                                @can('crm-admin')
                                    <li><a href="#">Admin <span class="fa fa-chevron-down"></span></a>
                                        <ul class="nav child_menu">
                                            @can('insurance-company-list')
                                                <li><a href="{{ route('insurancecompany.index') }}">Insurance Companies</a></li>
                                            @endcan
                                            @can('reason-list')
                                                <li><a href="{{ route('reason.index') }}">Reasons</a></li>
                                            @endcan
                                            @can('status-list')
                                                <li><a href="{{ route('status.index') }}">Status</a></li>
                                            @endcan
                                            @can('payment-mode-list')
                                                <li><a href="{{ route('paymentmode.index') }}">Payment Modes</a></li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    </ul>
                @endcan
                @can('customers-list')
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
                @can('renewals-upload')
                    <ul class="nav side-menu">
                        <li><a><i class="fa fa-quote-left"></i> Renewals <span class="fa fa-chevron-down"></span></a>
                            <ul class="nav child_menu">
                                <li><a href="{{ url('renewals-upload') }}">Upload</a></li>
                                <li><a href="{{ url('renewals-list') }}">Uploaded Leads</a></li>
                            </ul>
                        </li>
                    </ul>
                @endcan
                @can('claim-list')
                    <ul class="nav side-menu">
                        <li><a><i class="fa fa-quote-left"></i> Claims <span class="fa fa-chevron-down"></span></a>
                            <ul class="nav child_menu">
                                <li><a href="{{ url('claim/claims') }}">Claims</a></li>

                                @can('crm-admin')
                                    <li><a href="#">Admin <span class="fa fa-chevron-down"></span></a>
                                        <ul class="nav child_menu">
                                            @can('type-of-insurance-list')
                                                <li><a href="{{ url('claim/typeofinsurance') }}">Type of Insurance</a></li>
                                            @endcan
                                            @can('sub-type-of-insurance-list')
                                                <li><a href="{{ url('claim/subtypeofinsurance') }}">Sub Type of Insurance</a>
                                                </li>
                                            @endcan
                                            @can('claims-status-list')
                                                <li><a href="{{ url('claim/claimsstatus') }}">Claim Status</a></li>
                                            @endcan
                                            @can('car-repair-coverage-list')
                                                <li><a href="{{ url('claim/carrepaircoverage') }}">Car Repair Coverage</a></li>
                                            @endcan
                                            @can('car-repair-type-list')
                                                <li><a href="{{ url('claim/carrepairtype') }}">Car Repair Type</a></li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    </ul>
                @endcan
                @can('aml-list')
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
                @if (Auth::user()->hasRole('advisor') || Auth::user()->hasRole('pa') || Auth::user()->hasRole('invoicing') || Auth::user()->hasRole('production_approval_manager'))
                    <ul class="nav side-menu">
                        <li><a href="{{ url('ftcform') }}"><i></i> Policy Issuance </a>
                    </ul>
                @endif
                @can('telemarketing-list')
                    <ul class="nav side-menu">
                        <li><a><i class="fa fa-quote-left"></i> Telemarketing <span class="fa fa-chevron-down"></span></a>
                            <ul class="nav child_menu">
                                <li><a href="{{ url('telemarketing/tmleads') }}">TM Leads</a></li>
                                @can('tm-upload-leads-list')
                                    <li><a href="{{ url('telemarketing/tmuploadlead') }}">Upload TM Leads</a></li>
                                @endcan
                                @can('crm-admin')
                                    <li><a href="#">Admin <span class="fa fa-chevron-down"></span></a>
                                        <ul class="nav child_menu">
                                            @can('tm-insurance-type-list')
                                                <li><a href="{{ url('telemarketing/tminsurancetype') }}">TM Type of Insurance</a>
                                                </li>
                                            @endcan
                                            {{-- @can('tm-call-status-list')
                                <li><a href="{{ url('telemarketing/tmcallstatus') }}">TM Call Status</a></li>
                            @endcan --}}
                                            @can('tm-lead-status-list')
                                                <li><a href="{{ url('telemarketing/tmleadstatus') }}">TM Lead Status</a></li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    </ul>
                @endcan
                @can('crm-admin')
                    <ul class="nav side-menu">
                        <li><a><i class="fa fa-user"></i> Admin <span class="fa fa-chevron-down"></span></a>
                            <ul class="nav child_menu">
                                @can('users-list')
                                    <li><a href="{{ url('admin/users') }}">Users</a></li>
                                @endcan
                                @can('role-list')
                                    <li><a href="{{ url('admin/roles') }}">Roles</a></li>
                                @endcan
                                @can('teams-list')
                                    <li><a href="{{ url('quotes/teams') }}">Teams</a></li>
                                @endcan
                                @can('teams-list')
                                    <li><a href="{{ url('quotes/leadstatus') }}">Lead Status</a></li>
                                @endcan
                            </ul>
                        </li>
                    </ul>
                @endcan
            </div>
        </div>
        <!-- /sidebar menu -->
    </div>
</div>
