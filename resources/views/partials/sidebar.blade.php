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
            <li><a href="{{ url('dashboard') }}"><i class="fa fa-laptop"></i> Dashboard </a>
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
                @can('carquotes-list')
                    <li><a href="{{ url('quotes/carquotes') }}">Car Quotes</a></li>
                @endcan
                @can('carquotes-list')
                    <li><a href="{{ url('quotes/healthquotes') }}">Health Quotes</a></li>
                @endcan
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
        <ul class="nav side-menu">
            <li><a href="{{ url('customer') }}"><i class="fa fa-user"></i> Customer </a>
        </ul>
    </div>
    </div>
    <!-- /sidebar menu -->
    </div>
</div>
