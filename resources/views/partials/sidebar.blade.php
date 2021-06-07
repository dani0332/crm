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
        <ul class="nav side-menu">
            <li> <a><i class="fa fa-dashboard"></i> Dashboard  <span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">
                <li><a href="{{ url('dashboard') }}">Over All Dashboard</a></li>
            </ul>
        </ul>
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
                <li><a href="{{ url('rewards/reward-categories') }}">Reward Category</a></li>
                @endcan
                @can('reward-tags-list')
                <li><a href="{{ url('rewards/reward-tags') }}">Reward Tags</a></li>
                @endcan
            </ul>
            </li>
        </ul>

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
        <ul class="nav side-menu">
            <li><a><i class="fa fa-desktop"></i> Trans App <span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">
                @can('transapp-list')
                    <li><a href="{{ route('home') }}">Home</a></li>
                @endcan
                <li><a href="#">Admin <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        @can('insurance-company-list')
                            <li><a href="{{ route('insurancecompany.index') }}">Insurance Company</a></li>
                        @endcan
                        @can('handler-list')
                            <li><a href="{{ route('handler.index') }}">Handler</a></li>
                        @endcan
                        @can('reason-list')
                            <li><a href="{{ route('reason.index') }}">Reason</a></li>
                        @endcan
                        @can('status-list')
                            <li><a href="{{ route('status.index') }}">Status</a></li>
                        @endcan

                        @can('payment-mode-list')
                            <li><a href="{{ route('paymentmode.index') }}">Payment Mode</a></li>
                        @endcan
                    </ul>
                </li>
                @can('transapp-list')
                    <li><a href="#">Transaction <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">

                            <li class="sub_menu"><a href="{{ route('transaction.create') }}">Create a Transaction </a></li>
                            <li class="sub_menu"><a href="{{ route('reissue_view') }}">Cancel & Re-Issue Transaction </a></li>
                            <li class="sub_menu"><a href="{{ route('cancel_view') }}">Cancel Transaction (without re-issue) </a></li>
                        </ul>
                    </li>
                @endcan
            </ul>
            </li>
        </ul>
        <ul class="nav side-menu">
            <li><a href="{{ url('customer') }}"><i class="fa fa-user"></i> Customer </a>
        </ul>
        <ul class="nav side-menu">
            <li><a><i class="fa fa-quote-left"></i> Claims <span class="fa fa-chevron-down"></span></a>
                <ul class="nav child_menu">
                    @can('claim-list')
                        <li><a href="{{ url('claim/claims') }}">Claims</a></li>
                    @endcan
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
                            @can('rent-a-car-list')
                                <li><a href="{{ url('claim/rentacar') }}">Rent a Car</a></li>
                            @endcan
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>

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


    </div>
    </div>
    <!-- /sidebar menu -->
    </div>
</div>
