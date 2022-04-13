@extends('layouts.app')
@section('title', 'View AMT')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script>
    $(document).ready(function() {
        var isAdmin = JSON.parse('<?php echo json_encode(Auth::user()->hasRole("ADMIN")); ?>');
        var isManagerOrDeputy = $("#isManagerOrDeputy").val();
        var isRenewalUser = JSON.parse('<?php echo json_encode(Auth::user()->hasRole("GM_RENEWAL_ADVISOR")); ?>');
        $(document).on("change", "#amtLeadID", function () {
            var idsArray = $('#selectTmLeadId').val();
            idsArray = idsArray+ ',' + $(this).val() + ',';
            $('#selectTmLeadId').val(idsArray.replace(/^,|,$/g, ''));
            var countSelectedTmLeadIds = document.querySelectorAll('#amtLeadID:checked').length;
            if (countSelectedTmLeadIds > 0) {
                $("#amt-leads-assign-div").show(300);
            } else {
                $('#checkAllAMT').prop('checked', false);
                $("#amt-leads-assign-div").hide(300);
            }
        });
        $("#checkAllAMT,#amtLeadID").click(function () {
            if ($(this).is(":checked")) {
                $("#amt-leads-assign-div").show(300);
            } else {
                $("#amt-leads-assign-div").hide(200);
            }
            $('input:checkbox').not(this).prop('checked', this.checked);

            var idsArray = $('#selectTmLeadId').val();
            $('input:checkbox').each(function (i, item) {
                idsArray = idsArray + $(item).val() + ',';
            });
            $('#selectTmLeadId').val(idsArray.replace(/^,|,$/g, ''));
        });

        var dataTableColumns = [];

        if(isRenewalUser) {

        if (isManagerOrDeputy === "1") {
            dataTableColumns.push({
                data: "id",
                name: "id",
                render: function(data, type, row, meta) {
                    return (
                        '<input type="checkbox" id="amtLeadID" class="tmleadCheckbox" name="amtLeadID" value="' +
                        data + '">'
                    );
                },
            });
        }
        dataTableColumns.push({
                data: 'code',
                name: 'code',
                render: function (data, type, row) {
                    var type = row.code && row.code.indexOf('HEA-') > -1 ? 'health' : 'business';
                    var href = '/medical/amt/' + row.uuid;
                    return "<a href='" + href + "'>" + row.code + "</a>";
                }
            },
            { data: "first_name", name: "first_name" },
            { data: "last_name", name: "last_name" },
            { data: "leadStatus", name: "leadStatus" },
            { data: "advisor_id_text", name: "advisor_id_text" },
            { data: "premium", name: "premium" },
            { data: "company_name", name: "company_name" },
            // { data: "next_followup_date", name: "next_followup_date" },
            // { data: "lost_reason", name: "lost_reason" },
            // { data: "source", name: "source" },
            { data: "policy_number", name: "policy_number" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },

            );
            var buttons = [];
            var amtDataTable = $(".amt-data-table").DataTable({
                ordering: false,
                info: true,
                searching: false,
                dom: 'rBfrtip',
                bLengthChange: false,
                serverSide: true,
                paging: true,
                processing: true,
                columnDefs: [
                        // { orderable: false, targets: [1,2,3,4,5,6,7,9,10] }
                        ],
                buttons: isAdmin || isManagerOrDeputy ? [{
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel-o" style="color:green;" ></i><div style="font-weight:bold;">Export</div>',
                        title: 'Group Medical Listing',
                        action: newexportaction
                    }] : [],
                ajax: {
                    url: config.routes.amtDataTable,
                    data: function (d) {
                        d.created_at_start = $("#created_at_start").val();
                        d.created_at_end = $("#created_at_end").val();
                        d.first_name = $("#first_name").val();
                        d.last_name = $("#last_name").val();
                        // d.email = $("#email").val();
                        // d.mobile_no = $("#mobile_no").val();
                        d.leadStatus = $("#leadStatus").val();
                        d.advisor_id = $("#advisor_id").val();
                        d.code = $("#code").val();
                    },
                },
                columns: dataTableColumns,
            });

        } else {
        if (isManagerOrDeputy === "1") {
            dataTableColumns.push({
                data: "id",
                name: "id",
                render: function(data, type, row, meta) {
                    return (
                        '<input type="checkbox" id="amtLeadID" class="tmleadCheckbox" name="amtLeadID" value="' +
                        data + '">'
                    );
                },
            });
        }
        dataTableColumns.push({
                data: 'code',
                name: 'code',
                render: function (data, type, row) {
                    var type = row.code && row.code.indexOf('HEA-') > -1 ? 'health' : 'business';
                    var href = '/medical/amt/' + row.uuid;
                    return "<a href='" + href + "'>" + row.code + "</a>";
                }
            },
            { data: "first_name", name: "first_name" },
            { data: "last_name", name: "last_name" },
            { data: "leadStatus", name: "leadStatus" },
            { data: "advisor_id_text", name: "advisor_id_text" },
            { data: "premium", name: "premium" },
            { data: "company_name", name: "company_name" },
            { data: "next_followup_date", name: "next_followup_date" },
            { data: "lost_reason", name: "lost_reason" },
            { data: "source", name: "source" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },

            );
            var buttons = [];
            var amtDataTable = $(".amt-data-table").DataTable({
                ordering: true,
                info: true,
                searching: false,
                dom: 'rBfrtip',
                bLengthChange: false,
                serverSide: true,
                paging: true,
                processing: true,
                columnDefs: [
                        { orderable: false, targets: [1,2,3,4,5,6,7,9,10] }
                        ],
                buttons: isAdmin || isManagerOrDeputy ? [{
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel-o" style="color:green;" ></i><div style="font-weight:bold;">Export</div>',
                        title: 'Group Medical Listing',
                        action: newexportaction
                    }] : [],
                ajax: {
                    url: config.routes.amtDataTable,
                    data: function (d) {
                        d.created_at_start = $("#created_at_start").val();
                        d.created_at_end = $("#created_at_end").val();
                        d.first_name = $("#first_name").val();
                        d.last_name = $("#last_name").val();
                        d.email = $("#email").val();
                        d.mobile_no = $("#mobile_no").val();
                        d.leadStatus = $("#leadStatus").val();
                        d.advisor_id = $("#advisor_id").val();
                        d.code = $("#code").val();
                    },
                },
                columns: dataTableColumns,
            });
        }

        $('#amt_sbmt').on('click', function(e) {
            e.preventDefault();
            amtDataTable.draw();
        });
        $("#amt_reset").click(function() {
            $(this).closest('form').trigger('reset');
            amtDataTable.draw();
        });

            $(".toggle-btn-2").on("click", function() {
                $(".show-visual-cards").addClass("hideme");
                $(".show-visual-cards").removeClass("showme");
                $(".show-container").addClass("showme");
                $(".show-container").removeClass("hideme");
                $(this).addClass("active");
                $(".toggle-btn").removeClass("active");
            });
            $(".toggle-btn").on("click", function() {
                $(".show-visual-cards").addClass("showme");
                $(".show-visual-cards").removeClass("hideme");
                $(".show-container").removeClass("showme");
                $(".show-container").addClass("hideme");
                $(this).addClass("active");
                $(".toggle-btn-2").removeClass("active");
            });
    });

    var ENDPOINT = "{{ url('/') }}";
        var page;
        var temp_status = '';
        function loadMore(status) {
            if(localStorage.getItem('page'+status) == null)
                page = 2;
            else
                page = localStorage.getItem('page'+status);
            infinteLoadMore(page,status);
        }
        function infinteLoadMore(page,status) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                    url: ENDPOINT + "/quotes/records?page=" + page +"&modelType=" + "Business" + "&status=" + status,
                    datatype: "html",
                    type: "post",
                    beforeSend: function () {
                        $('.loader').show();
                    }
                })
                .done(function (response) {
                    $('.loader').hide();
                    if (response.length == 0) {
                        localStorage.removeItem('page'+status, page);
                        $("#load_more_btn"+status).hide();
                        alert("Nothing to Show");
                        return;
                    }
                    $(".status_list"+status+" li:last").append(response);
                    temp_status = status;
                    page = parseInt(page) + 1;
                    localStorage.setItem('page'+status, page);
                })
                .fail(function (jqXHR, ajaxOptions, thrownError) {
                    console.log('Server error occured');
                });
        }

        function searchTerm(element) {
            var term = $(element).val();
            var status = $(element).attr('name');
            if(term) {
                $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                    url: ENDPOINT + "/quotes/records/search?term=" + term + "&status=" + status +"&modelType=" + "Business",
                    datatype: "html",
                    type: "post",
                    beforeSend: function () {
                        $('.loader').show();
                    }
                })
                .done(function (response) {
                    $("#load_more_btn"+status).hide();
                    $(element).val('');
                    $('.loader').hide();
                    if (response.length == 0) {
                        alert("Nothing to Show");
                        return;
                    }
                    $(".status_list"+status).empty();
                    $(".status_list"+status).append(response);
                })
                .fail(function (jqXHR, ajaxOptions, thrownError) {
                    console.log('Server error occured');
                });
            }

        }
        window.onload = function () {
            window.localStorage.clear();
        }

    function newexportaction(e, dt, button, config) {
                var self = this;
                var oldStart = dt.settings()[0]._iDisplayStart;
                dt.one('preXhr', function(e, s, data) {
                    data.start = 0;
                    data.length = 2147483647;
                    dt.one('preDraw', function(e, settings) {
                        if (button[0].className.indexOf('buttons-copy') >= 0) {
                            $.fn.dataTable.ext.buttons.copyHtml5.action.call(self, e, dt, button,
                                config);
                        } else if (button[0].className.indexOf('buttons-excel') >= 0) {
                            $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                                $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt,
                                    button, config) :
                                $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt,
                                    button, config);
                        } else if (button[0].className.indexOf('buttons-csv') >= 0) {
                            $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                                $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button,
                                    config) :
                                $.fn.dataTable.ext.buttons.csvFlash.action.call(self, e, dt, button,
                                    config);
                        } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                            $.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config) ?
                                $.fn.dataTable.ext.buttons.pdfHtml5.action.call(self, e, dt, button,
                                    config) :
                                $.fn.dataTable.ext.buttons.pdfFlash.action.call(self, e, dt, button,
                                    config);
                        } else if (button[0].className.indexOf('buttons-print') >= 0) {
                            $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
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
    <div class="row">
        <div class="x_panel" style="overflow:hidden">
            <div class="x_title">
                <h2>Group Medical Leads</h2>
                <ul class="nav navbar-right panel_toolbox">
                    @can('gm-quotes-create')
                    <li><a href="{{ url('medical/amt/create') }}" class="btn btn-warning btn-sm">Create Lead</a></li>
                    @endcan
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <button type="button" class="btn btn-warning btn-sm toggle-btn  float-right change-layout">Cards View</button>
                <button type="button" class="btn btn-warning btn-sm toggle-btn-2 active float-right change-layout">List View</button>
                <div class="show-visual-cards hideme">
                    <x-group-medical-visual
                        :model="$model"
                        :dropdownSource="$leadStatuses"
                    />
                </div>
                <div class="show-container showme">
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    <form method="POST" id="search-claims" action={{ route('amt.index') }} class="form-horizontal form-label-left" role="form" data-parsley-validate="" novalidate="" autocomplete="off">
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="searchfield">CREATED DATE START</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="created_at_start" id="created_at_start" >
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="searchfield">CREATED DATE END</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="created_at_end" id="created_at_end" >
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if (!Auth::user()->isRenewalAdvisor())
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="searchfield">Next Followup Date Start</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="next_followup_date" id="next_followup_date" >
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="searchfield">Next Followup Date Start</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="next_followup_date_end" id="next_followup_date_end" >
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="searchfield">CDB ID</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="code" id="code" >
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="searchfield">FIRST NAME</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="first_name" id="first_name" >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="searchfield">LAST NAME</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="last_name" id="last_name" >
                                    </div>
                                </div>
                            </div>
                            @if (!Auth::user()->isRenewalAdvisor())
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="searchfield">EMAIL</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="email" id="email" >
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="searchfield">MOBILE NUMBER</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="mobile_no" id="mobile_no" >
                                    </div>
                                </div>
                            </div>
                            @if (!Auth::user()->isRenewalAdvisor())
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="searchfield">ASSIGNED TO</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <select class="form-control" id="advisor_id" name="advisor_id">
                                            <option value="">Select Assigned To</option>
                                            <option value="-1">UnAssigned</option>
                                            @foreach ($advisors as $advisor)
                                                <option value="{{ $advisor->id }}">{{ $advisor->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @if (!Auth::user()->isRenewalAdvisor())
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="searchfield">LEAD STATUS</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <select class="form-control" id="leadStatus" name="leadStatus">
                                            <option value="">Select Lead Status</option>
                                            @foreach ($leadStatuses as $leadStatus)
                                                <option value="{{ $leadStatus->id }}">{{ $leadStatus->text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col">

                            </div>
                        </div>
                        @endif
                        <div class="item form-group">
                            <div class="col">

                            </div>
                            <div class="col">
                                <ul class="nav navbar-right panel_toolbox">
                                    <li><input id="amt_sbmt" type="submit" class="btn btn-warning btn-sm" value="Search"></li>
                                    <li><input type="reset" id="amt_reset" class="btn btn-warning btn-sm"></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                    <br />
                    <form method="post" action="/quotes/manualLeadAssign" class="form-horizontal form-label-left" role="form"
                    data-parsley-validate="" novalidate="" autocomplete="off">
                    {{ csrf_field() }}
                    @method('POST')
                    <input type="hidden" name="modelType" value="business" />
                    <div class="row" id="amt-leads-assign-div" style="display: none">
                        <div class="col-md-12 col-sm-12">
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2>Assign Leads</h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content" id="form-to-show">
                                    <div class="item form-group">
                                        <label class="col-form-label col-md-2 col-sm-2" for="Assign To">Assign
                                            To</label>
                                        <div class="col-md-6 col-sm-6">
                                            <select class="form-control" id="assigned_to_id_new"
                                                name="assigned_to_id_new">
                                                @foreach ($advisors as $handler)
                                                    <option value="{{ $handler->id }}">{{ $handler->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="item form-group">
                                        <label class="col-form-label col-md-2 col-sm-2" for="first-name"> </label>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="input-group">
                                                <button type="submit" id="assignToBtn"
                                                    name="assignToBtn"
                                                    class="btn btn-warning btn-sm">Assign</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="selectTmLeadId" name="selectTmLeadId" value="">
                    <input type="hidden" id="isManagerOrDeputy" name="isManagerOrDeputy" value="{{ $isManagerORDeputy }}">
                    </form>
                    <table class="table table-striped jambo_table amt-data-table" style="width:100%">
                        <thead>
                            <tr>
                                @if ($isManagerORDeputy == '1')
                                    <th style="width: 15px;"><input type="checkbox" id="checkAllAMT"
                                            name="checkAllAMT" value=""></th>
                                @endif
                                <th>CDB ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Lead Status</th>
                                <th>Assigned To</th>
                                <th>Premium</th>
                                <th>Company Name</th>
                                @if (Auth::user()->isRenewalAdvisor())<th>Policy Number</th>@endif
                                @if (!Auth::user()->isRenewalAdvisor())<th>Next FollowUp Date</th>@endif
                                @if (!Auth::user()->isRenewalAdvisor())<th>Lost Reason</th>@endif
                                @if (!Auth::user()->isRenewalAdvisor())<th>Source</th>@endif
                                <th>Created At</th>
                                <th>Updated At</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
