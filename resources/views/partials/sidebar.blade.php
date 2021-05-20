<div class="col-md-3 left_col">
    <div class="left_col scroll-view">
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
            <li><a><i class="fa fa-gift"></i> Rewards <span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">
                <li><a href="{{ url('rewards/partner') }}">Partners</a></li>
                <li><a href="{{ url('rewards/reward') }}">Rewards</a></li>
                <li><a href="index3.html">Rewards Category</a></li>
            </ul>
            </li>
        </ul>
        </div>
    </div>
    <!-- /sidebar menu -->
    </div>
</div>