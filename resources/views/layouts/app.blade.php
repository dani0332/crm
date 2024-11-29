<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc, . -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('image/favicon.ico') }}">
    <title>@yield('title') | {{config('constants.APP_NAME')}}</title>
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
    <!-- <link href="{{ asset('build/css/custom.min.css') }}" rel="stylesheet"> -->
    <link href="{{ asset('old/style.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-bs/css/dataTables.bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css') }}"
        rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css') }}" rel="stylesheet">
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
                @include('partials.messages')
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
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="{{ asset('old/Toaster.js') }}"></script>

    @php
    $userId = Auth::user()->id;
    @endphp
    <!-- iCheck -->
    <script src="{{ asset('vendors/iCheck/icheck.min.js') }}"></script>
    <!-- Custom Theme Scripts -->
    <script src="{{ asset('old/custom.js') }}"></script>
    <script>
        function getStatusText(statusId){
            var statusText = '';
            switch(parseInt(statusId)){
                case 1:
                    statusText = 'Online';
                    break;
                case 2:
                    statusText = 'Offline';
                    break;
                case 3:
                    statusText = 'Unavailable';
                    break;
                case 4:
                    statusText = 'Sick';
                    break;
                case 5:
                    statusText = 'On leave';
                    break;
                default:
                    statusText = 'Unavailable'
                    break;
            }
            return statusText;
        }

        function changeAvailiblity(data, self)
        {
            if(userId == data.userId){
                $('#online-status-div').hide();
                $('#offline-status-div').hide();
                $('#unavailable-status-div').hide();
                if(data.status == 1) {
                    $('#online-status-div').show();
                }
                if(data.status == 2)  {
                    $('#offline-status-div').show();
                }
                if(data.status != 1 && data.status != 2 ) {
                    $('#unavailable-status-div').show();
                }
            }
            var statusText = getStatusText(data.status);
            $(self).parent().find('.status-text').text(statusText);
            $(self).parent().find('#is_active').prop('checked', data.status == 1 ? true: false);
            $(self).parent().find('#is_active').removeClass('danger').removeClass('success').addClass(data.status == 1 ? 'success': 'danger');
        }

        var config = {
            routes: {
                user_datatable_route: "{{ route('users.index') }}",
                role_datatable_route: "{{ route('roles.index') }}",
                vehicledepreciation_datatable_route: "{{ route('vehicledepreciation.index') }}",
                customer_data_table_route: "{{ route('customers-list') }}",
                discount_base_data_table_route: "{{ route('base.index') }}",
                load_auditable: "{{ url('auditable') }}",
                load_apilogs: "{{ url('insurer-logs') }}",
                load_dashboard_stats: "{{ url('dashboard-stats') }}",
                insurancecompany_datatable_route: "{{ route('insurancecompany.index') }}",
                handler_datatable_route: "{{ route('handler.index') }}",
                reason_datatable_route: "{{ route('reason.index') }}",
                status_datatable_route: "{{ route('status.index') }}",
                paymentmode_datatable_route: "{{ route('paymentmode.index') }}",
                transaction_datatable_route: "{{ route('transaction.index') }}",
                re_issue_transaction_form: "{{ route('re_issue_transaction_form') }}",
                valuation_api_route: "{{ Config::get('constants.valuation_api_route') }}",
                valuation_api_token: "{{ Config::get('constants.valuation_api_token') }}",
                tminsurancetype_datatable_route: "{{ route('tminsurancetype.index') }}",
                tmcallstatus_datatable_route: "{{route('tmcallstatus.index') }}",
                tmleadstatus_datatable_route: "{{ route('tmleadstatus.index') }}",
                tmlead_datatable_route: "{{ route('tmleads-list') }}",
                tmuploadlead_datatable_route: "{{ route('tmuploadlead-list') }}",
                age_discount_datatable_route: "{{ route('age.index') }}",
                renewals_leads_datatable_route: "{{ route('renewals-uploaded-leads-list') }}",
                leadassignmentDataTable: "{{ route('leadassignment.index') }}",
                amtDataTable: "{{ route('amt.index') }}",
                activitiesDataTable: "{{ route('activities.index') }}",
                renewals_batches_datatable_route: "{{ route('renewals-batches') }}",
                lead_allocation_index_route: "{{ route('lead-allocation.index') }}",
                renewal_uploaded_leads: "{{ route('renewals-uploaded-leads-list') }}",
                renewal_base_url: "{{ url('renewals') }}",
                car_lead_allocation_index_route: "{{ route('car-lead-allocation.index') }}",
                advisor_conversion_report_route: "{{ url('reports/advisor-conversion')  }}",
                teams_datatable_route: "{{ route('team.index') }}",
                commercial_keywords_datatable_route: "{{ route('admin.commercial.keywords') }}",
                commercial_vehicles_datatable_route: "{{ route('admin.configure.commerical.vehicles') }}",
                renewal_batched_datatable_route: "{{ route('renewal-batches-list') }}",
            },
            _token: "{{ csrf_token() }}"
        };
        // TM Leads: Expost data into csv
        function newexportaction(e, dt, button, config) {
            var self = this;
            var oldStart = dt.settings()[0]._iDisplayStart;
            dt.one('preXhr', function(e, s, data) {
                data.start = 0;
                data.length = 2147483647;
                dt.one('preDraw', function(e, settings) {
                    if (button[0].className.indexOf('buttons-csv') >= 0) {
                        $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                            $.fn.dataTable.ext.buttons.csvHtml5.action.call(
                                self,
                                e,
                                dt,
                                button,
                                config,
                            ) :
                            $.fn.dataTable.ext.buttons.csvFlash.action.call(
                                self,
                                e,
                                dt,
                                button,
                                config,
                            );
                    }
                    dt.one('preXhr', function(e, s, data) {
                        settings._iDisplayStart = oldStart;
                        data.start = oldStart;
                    });
                    setTimeout(dt.ajax.reload, 0);
                    return false;
                });
            });
            dt.ajax.reload();
        }
    </script>
    <script src="{{ asset('old/customjs.js') }}"></script>
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
