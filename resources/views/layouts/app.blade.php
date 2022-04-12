<?php
$appName = Config::get('constants.APP_NAME');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('image/favicon.ico') }}">
    <title>@yield('title') | <?php echo $appName; ?></title>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <!-- Bootstrap -->
    <link href="{{ asset('vendors/bootstrap/dist/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />

    <!-- Font Awesome -->
    <link href="{{ asset('vendors/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet">
    <!-- NProgress -->
    <link href="{{ asset('vendors/nprogress/nprogress.css') }}" rel="stylesheet">
    <!-- bootstrap-daterangepicker -->
    <link href="{{ asset('vendors/bootstrap-daterangepicker/daterangepicker.css') }}" rel="stylesheet">

    <!-- bootstrap-wysiwyg -->
    <link href="{{ asset('vendors/google-code-prettify/bin/prettify.min.css') }}" rel="stylesheet">

    <!-- Custom styling plus plugins -->
    <link href="{{ asset('build/css/custom.min.css') }}" rel="stylesheet">
    <link href="{{ asset('build/style.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-bs/css/dataTables.bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css') }}"
        rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css') }}"
        rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css') }}" rel="stylesheet">
    <link href="https://www.jquery-az.com/jquery/css/bootstrap-markdown-editor.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="{{ asset('css/crm.css') }}" rel="stylesheet">

    <!-- iCheck -->
    <link href="{{ asset('vendors/iCheck/skins/flat/green.css') }}" rel="stylesheet">
    <style>
        .loader {
            position: fixed;
            left: 0px;
            top: 0px;
            width: 100%;
            height: 100%;
            z-index: 9999;
            opacity: 0.7;
            background: url('//upload.wikimedia.org/wikipedia/commons/thumb/e/e5/Phi_fenomeni.gif/50px-Phi_fenomeni.gif') 50% 50% no-repeat rgb(249, 249, 249);
            display: none;
        }

    </style>
</head>

<body class="nav-md">
    <div class="loader">
    </div>
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <form action="" id="delete-form" method='POST' style="margin-top: -2px;">
                        @csrf
                        @method('DELETE')
                        <button type='submit' type="button" class="btn btn-danger">Delete</button>
                    </form>

                </div>
            </div>
        </div>
    </div>
    <div class="container body">
        <div class="main_container">
            @include('partials.sidebar')
            @include('partials.topnav')
            <div class="right_col" role="main">
                @yield('content')
            </div>

            @include('partials.footer')

        </div>
    </div>
    <!-- jQuery -->
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <!-- Bootstrap -->
    <script src="{{ asset('vendors/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <!-- FastClick -->
    <script src="{{ asset('vendors/fastclick/lib/fastclick.js') }}"></script>
    <!-- NProgress -->
    <script src="{{ asset('vendors/nprogress/nprogress.js') }}"></script>
    <!-- bootstrap-wysiwyg -->
    <script src="{{ asset('vendors/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
    <script src="{{ asset('vendors/jquery.hotkeys/jquery.hotkeys.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.1.3/ace.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/0.3.2/marked.min.js"></script>
    <script src="https://www.jquery-az.com/jquery/js/bootstrap-markdown-editor.js"></script>
    <!-- Chart.js -->
    <script src="{{ asset('vendors/Chart.js/dist/Chart.min.js') }}"></script>
    <!-- jQuery Sparklines -->
    <script src="{{ asset('vendors/jquery-sparkline/dist/jquery.sparkline.min.js') }}"></script>
    <!-- Flot -->
    <script src="{{ asset('vendors/Flot/jquery.flot.js') }}"></script>
    <script src="{{ asset('vendors/Flot/jquery.flot.pie.js') }}"></script>
    <script src="{{ asset('vendors/Flot/jquery.flot.time.js') }}"></script>
    <script src="{{ asset('vendors/Flot/jquery.flot.stack.js') }}"></script>
    <script src="{{ asset('vendors/Flot/jquery.flot.resize.js') }}"></script>
    <!-- Flot plugins -->
    <script src="{{ asset('vendors/flot.orderbars/js/jquery.flot.orderBars.js') }}"></script>
    <script src="{{ asset('vendors/flot-spline/js/jquery.flot.spline.min.js') }}"></script>
    <script src="{{ asset('vendors/flot.curvedlines/curvedLines.js') }}"></script>
    <!-- DateJS -->
    <script src="{{ asset('vendors/DateJS/build/date.js') }}"></script>
    <!-- bootstrap-daterangepicker -->
    <script src="{{ asset('vendors/moment/min/moment.min.js') }}"></script>
    <script src="{{ asset('vendors/bootstrap-daterangepicker/daterangepicker.js') }}"></script>

    <script src="{{ asset('vendors/google-code-prettify/src/prettify.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons/js/buttons.flash.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-keytable/js/dataTables.keyTable.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-scroller/js/dataTables.scroller.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/jquery.validate.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <!-- iCheck -->
    <script src="{{ asset('vendors/iCheck/icheck.min.js') }}"></script>
    <!-- Custom Theme Scripts -->
    <script src="{{ asset('build/js/custom.js') }}"></script>
    <script>
        // global app configuration object
        var config = {
            routes: {
                partner_datatable_route: "{{ route('partner.index') }}",
                user_datatable_route: "{{ route('users.index') }}",
                role_datatable_route: "{{ route('roles.index') }}",
                carquote_datatable_route: "{{ route('carquotes.index') }}",
                carquote_resubmitap_route: "{{ url('quotes/carquotes/resubmit_api') }}",
                healthquote_datatable_route: "{{ route('healthquotes.index') }}",
                reward_datatable_route: "{{ route('reward.index') }}",
                reward_categories_datatable_route: "{{ route('reward-categories.index') }}",
                reward_tags_datatable_route: "{{ route('reward-tags.index') }}",
                claim_datatable_route: "{{ route('claims.index') }}",
                typeofinsurance_datatable_route: "{{ route('typeofinsurance.index') }}",
                vehicledepreciation_datatable_route: "{{ route('vehicledepreciation.index') }}",
                subtypeofinsurance_datatable_route: "{{ route('subtypeofinsurance.index') }}",
                claimsstatus_datatable_route: "{{ route('claimsstatus.index') }}",
                carrepaircoverage_datatable_route: "{{ route('carrepaircoverage.index') }}",
                carrepairtype_datatable_route: "{{ route('carrepairtype.index') }}",
                rentacar_datatable_route: "{{ route('rentacar.index') }}",
                customer_data_table_route: "{{ route('customer.index') }}",
                discount_base_data_table_route: "{{ route('base.index') }}",
                load_auditable: "{{ url('auditable') }}",
                //load_dashboard_stats: "{{ url('dashboard-stats') }}",
                insurancecompany_datatable_route: "{{ route('insurancecompany.index') }}",
                handler_datatable_route: "{{ route('handler.index') }}",
                reason_datatable_route: "{{ route('reason.index') }}",
                status_datatable_route: "{{ route('status.index') }}",
                paymentmode_datatable_route: "{{ route('paymentmode.index') }}",
                transaction_datatable_route: "{{ route('transaction.index') }}",
                re_issue_transaction_form: "{{ route('re_issue_transaction_form') }}",
                aml_datatable_route: "{{ route('aml.index') }}",
                valuation_api_route: "{{ Config::get('constants.valuation_api_route') }}",
                valuation_api_token: "{{ Config::get('constants.valuation_api_token') }}",
                tminsurancetype_datatable_route: "{{ route('tminsurancetype.index') }}",
                tmcallstatus_datatable_route: "{{ route('tmcallstatus.index') }}",
                tmleadstatus_datatable_route: "{{ route('tmleadstatus.index') }}",
                tmlead_datatable_route: "{{ route('tmleads.index') }}",
                tmuploadlead_datatable_route: "{{ route('tmuploadlead.index') }}",
                age_discount_datatable_route: "{{ route('age.index') }}",
                renewals_leads_datatable_route: "{{ route('renewals.index') }}",
                sanction_list_downloads_datatable_route: "{{ url('kyc/aml/download/history') }}",
                reward_sliders_datatable_route: "{{ route('reward-sliders.index') }}",
                searchLeadsDataTable: "{{ route('leadsearch.index') }}",
                leadassignmentDataTable: "{{ route('leadassignment.index') }}",
                myleadsDataTable: "{{ route('myleads.index') }}",
                amtDataTable: "{{ route('amt.index') }}",
            },
            _token: "{{ csrf_token() }}",
            image_path: "{{ \Config::get('constants.azure_storage_url') . 'myrewards/' }}",
            image_path_rewards_slider: "{{ \Config::get('constants.azure_storage_url') . 'myrewards/rewards-slider/' }}"
        };
    </script>
    <script src="{{ asset('build/js/customjs.js') }}"></script>
    <script src="{{ asset('build/js/tm_js.js') }}"></script>
    <script>
        $(document).ajaxError(function(event, jqxhr, settings, exception) {
            if (exception == 'Unauthorized') {
                alert('Session has expired.')
                window.location = '/login';
            }
        });
        $.fn.dataTable.ext.errMode = 'none'; // disable datatables error prompt
    </script>

</body>

</html>
