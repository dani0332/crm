<div class="col-md-3 left_col">
    <div class="left_col scroll-view" style="border: 0;backgroundlinear-gradient(0deg,#69d0fe,#4183bd);">
    <div class="navbar nav_title" style="border: 0;background:#eef1f4;">
        <a href="index.html" class="site_title">
            <img src='{{ asset("image/logo.png") }}' style="width:200px;height:30px;" />
        </a>
    </div>
    <div class="clearfix"></div>
    <br />

    <!-- sidebar menu -->
    <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
        <div class="menu_section">
        @can('crm-admin')
        <ul class="nav side-menu">
            <li> <a><i class="fa fa-dashboard"></i> Dashboard  <span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">
                <li><a href="{{ url('dashboard') }}">Over All Dashboard</a></li>
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
                @can('car-quotes-list')
                    <li><a href="{{ url('quotes/carquotes') }}">Car Quotes</a></li>
                @endcan
                @can('health-quotes-list')
                    <li><a href="{{ url('quotes/healthquotes') }}">Health Quotes</a></li>
                @endcan
            </ul>
            </li>
        </ul>
        @endcan
        @canany(['vehicle-depreciation-list', 'vehicle-valuation-list'])
        <ul class="nav side-menu">
            <li><a><i class="fa fa-car" aria-hidden="true"></i> Car <span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">
                <li><a href="{{ route('calculatevaluation') }}">Valuation</a></li>
                <li><a href="{{ url('valuation/vehicledepreciation') }}">Vehicle Depreciation</a></li>
            </ul>
            </li>
        </ul>
        @endcanany
        @can('transapp-list')
        <ul class="nav side-menu">
            <li><a><i class="fa fa-desktop"></i> Trans App <span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">
                <li><a href="{{ route('home') }}">Search Transaction</a></li>
                @can('transapp-create')
                <li><a href="{{ route('transaction.create') }}">Create Transaction</a></li>
                @endcan
                @can('transapp-edit')
                <li class="sub_menu"><a href="{{ route('reissue_view') }}">Cancel & Re-Issue Transaction</a></li>
                <li class="sub_menu"><a href="{{ route('cancel_view') }}">Cancel Transaction (without Re-Issue)</a></li>
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
            <li><a href="{{ url('customer') }}"><i class="fa fa-user"></i>Customers</a>
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
                                <li><a href="{{ url('claim/subtypeofinsurance') }}">Sub Type of Insurance</a></li>
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
        <!-- <ul class="nav side-menu">
             <li><a href="{{ url('ftcform') }}"><i ></i> FTC Form </a>
         </ul> -->
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
            </ul>
            </li>
        </ul>
        @endcan
    </div>
    </div>
    <!-- /sidebar menu -->
    </div>
</div>
