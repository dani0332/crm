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
    <link href="{{ asset('build/app.css') }}" rel="stylesheet">
  </head>
    <body class="nav-md">
    <div class="container body">
      <div class="main_container">
            @include('partials.sidebar')
            @include('partials.topnav')

            <div class="right_col" role="main" style="min-height: 1211px;">
                <div class="clearfix"></div>
                <div class="row">
                  <div class="col-md-12 col-sm-12 ">
                        <div id="root" class="root">
                            <div id="app"></div>
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
                user_datatable_route:"{{ route('users.index') }}",
                role_datatable_route:"{{ route('roles.index') }}",
                carquote_datatable_route:"{{ route('carquotes.index') }}",
                carquote_resubmitap_route:"{{ url('quotes/carquotes/resubmit_api') }}",
                healthquote_datatable_route:"{{ route('healthquotes.index') }}",
                claim_datatable_route:"{{ route('claims.index') }}",
                typeofinsurance_datatable_route:"{{ route('typeofinsurance.index') }}",
                subtypeofinsurance_datatable_route:"{{ route('subtypeofinsurance.index') }}",
                claimsstatus_datatable_route:"{{ route('claimsstatus.index') }}",
                carrepaircoverage_datatable_route:"{{ route('carrepaircoverage.index') }}",
                carrepairtype_datatable_route:"{{ route('carrepairtype.index') }}",
                rentacar_datatable_route:"{{ route('rentacar.index') }}",
                customer_data_table_route:"{{ route('customer.index') }}",
                load_auditable:"{{ url('auditable') }}",
                load_apilogs:"{{ url('insurer-logs') }}",
                load_dashboard_stats:"{{ url('dashboard-stats') }}",
                insurancecompany_datatable_route:"{{ route('insurancecompany.index') }}",
                handler_datatable_route:"{{ route('handler.index') }}",
                reason_datatable_route:"{{ route('reason.index') }}",
                status_datatable_route:"{{ route('status.index') }}",
                paymentmode_datatable_route:"{{ route('paymentmode.index') }}",
                transaction_datatable_route:"{{ route('transaction.index') }}",
                re_issue_transaction_form:"{{ route('re_issue_transaction_form') }}",
            },
            _token:"{{ csrf_token() }}"
        };
    </script>
     <script src="{{ mix('/js/app.js') }}"></script>
    </body>
</html>
