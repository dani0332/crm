<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('image/favicon.ico') }}">
    <title>@yield('title') | MyAlfredCrm</title>
    <!-- Bootstrap -->
    <link href="{{ asset('vendors/bootstrap/dist/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ asset('vendors/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet">
    <!-- Custom styling plus plugins -->
    <link href="{{ asset('build/css/custom.min.css') }}" rel="stylesheet">
    <link href="{{ asset('build/style.css') }}" rel="stylesheet">
  </head>
    <body class="nav-md">
    <div class="container body">
      <div class="main_container">
            @include('partials.sidebar')
            @include('partials.topnav')
            <div class="right_col" role="main">
                <div class="" style="height: 1000px">

                <div class="row" style="display: inline-block;">
                <div id="root" class="root">
                    <div id="app"></div>
                        <!-- <example-component></example-component> -->
                </div>
                <div class="tile_count">
                    <div class="col-md-2 col-sm-4  tile_stats_count">
                    <span class="count_top"><i class="fa fa-user"></i> Total Users</span>
                    <div class="count">2500</div>
                    <span class="count_bottom"><i class="green">4% </i> From last Week</span>
                    </div>
                    <div class="col-md-2 col-sm-4  tile_stats_count">
                    <span class="count_top"><i class="fa fa-clock-o"></i> Average Time</span>
                    <div class="count">123.50</div>
                    <span class="count_bottom"><i class="green"><i class="fa fa-sort-asc"></i>3% </i> From last Week</span>
                    </div>
                    <div class="col-md-2 col-sm-4  tile_stats_count">
                    <span class="count_top"><i class="fa fa-user"></i> Total Males</span>
                    <div class="count green">2,500</div>
                    <span class="count_bottom"><i class="green"><i class="fa fa-sort-asc"></i>34% </i> From last Week</span>
                    </div>
                    <div class="col-md-2 col-sm-4  tile_stats_count">
                    <span class="count_top"><i class="fa fa-user"></i> Total Females</span>
                    <div class="count">4,567</div>
                    <span class="count_bottom"><i class="red"><i class="fa fa-sort-desc"></i>12% </i> From last Week</span>
                    </div>
                    <div class="col-md-2 col-sm-4  tile_stats_count">
                    <span class="count_top"><i class="fa fa-user"></i> Total Collections</span>
                    <div class="count">2,315</div>
                    <span class="count_bottom"><i class="green"><i class="fa fa-sort-asc"></i>34% </i> From last Week</span>
                    </div>
                    <div class="col-md-2 col-sm-4  tile_stats_count">
                    <span class="count_top"><i class="fa fa-user"></i> Total Connections</span>
                    <div class="count">7,325</div>
                    <span class="count_bottom"><i class="green"><i class="fa fa-sort-asc"></i>34% </i> From last Week</span>
                    </div>
                </div>
        </div>
                </div>
            </div>
            @include('partials.footer')

      </div>
    </div>
    <!-- jQuery -->
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <!-- Bootstrap -->
   <script src="{{ asset('vendors/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <!-- FastClick -->
    <script src="{{ asset('vendors/fastclick/lib/fastclick.js') }}"></script>
	<script src="{{ asset('vendors/iCheck/icheck.min.js') }}"></script>
    <!-- Custom Theme Scripts -->
    <script src="{{ asset('build/js/custom.js') }}"></script>
    <script>
        // global app configuration object
        var config = {
            routes: {
                partner_datatable_route: "{{ route('partner.index') }}",
                user_datatable_route:"{{ route('users.index') }}",
                role_datatable_route:"{{ route('roles.index') }}",
                carquote_datatable_route:"{{ route('carquotes.index') }}",
                carquote_resubmitap_route:"{{ url('quotes/carquotes/resubmit_api') }}",
                healthquote_datatable_route:"{{ route('healthquotes.index') }}",
                reward_datatable_route:"{{ route('reward.index') }}",
                reward_categories_datatable_route:"{{ route('reward-categories.index') }}",
                reward_tags_datatable_route:"{{ route('reward-tags.index') }}",
                claim_datatable_route:"{{ route('claims.index') }}",
                typeofinsurance_datatable_route:"{{ route('typeofinsurance.index') }}",
                subtypeofinsurance_datatable_route:"{{ route('subtypeofinsurance.index') }}",
                claimsstatus_datatable_route:"{{ route('claimsstatus.index') }}",
                carrepaircoverage_datatable_route:"{{ route('carrepaircoverage.index') }}",
                carrepairtype_datatable_route:"{{ route('carrepairtype.index') }}",
                rentacar_datatable_route:"{{ route('rentacar.index') }}",
                customer_data_table_route:"{{ route('customer.index') }}",
                load_auditable:"{{ url('auditable') }}",
                load_dashboard_stats:"{{ url('dashboard-stats') }}",
                insurancecompany_datatable_route:"{{ route('insurancecompany.index') }}",
                handler_datatable_route:"{{ route('handler.index') }}",
                reason_datatable_route:"{{ route('reason.index') }}",
                status_datatable_route:"{{ route('status.index') }}",
                paymentmode_datatable_route:"{{ route('paymentmode.index') }}",
                transaction_datatable_route:"{{ route('transaction.index') }}",
                re_issue_transaction_form:"{{ route('re_issue_transaction_form') }}",
            },
            _token:"{{ csrf_token() }}",
            image_path:"{{ \Config::get('constants.azure_storage_url').'myrewards/' }}"
        };
    </script>
     <script src="{{ mix('/js/app.js') }}"></script>
    </body>
</html>
