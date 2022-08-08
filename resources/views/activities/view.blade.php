@extends('layouts.app')
@section('title', 'View Activity')
@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <style>
        
        #filters-div button {
            border-radius: 12px;
            border: 1px solid black;
            font-size: 20px;
            color: slategray;
            font-style: italic;
        }

        .custom-checkbox {
            cursor: pointer;
            display: block;
            font-size: 16px;
            line-height: 26px;
            margin: 0 0 20px;
            padding: 0 0 0 40px;
            position: relative;
        }

        .custom-checkbox input[type="checkbox"] {
            display: none;
        }

        .custom-checkbox span.checkbox {
            background-color: #fff;
            border: solid 2px #cccccc;
            border-radius: 50%;
            cursor: pointer;
            display: block;
            height: 26px;
            margin: 0px;
            position: absolute;
            left: 0;
            top: 0px;
            width: 26px;
        }

        .custom-checkbox input[type='checkbox']:checked+span.checkbox {
            background: #26B99A;
            border-color: #169F85;
            text-align: center;
        }

        .custom-checkbox input[type='checkbox']:checked+span.checkbox:before {
            content: "\f00c";
            color: #fff;
            font: normal normal normal 20px/1 FontAwesome;
        }

    </style>
    <script>

        var count = 1;

        function disabledDoneActivities() {
            $('.activityChk').each(function(index, el) {
                if ($(el).is(':checked') == true) {
                    $(el).attr('disabled', true);
                    $(el).closest('td').siblings().find('button').attr('disabled', true);
                }
            });
        }

        function updateStatus(id, e) {
            $.ajax({
                url: '/activities/updateStatus',
                method: "POST",
                data: {
                    activity_id: id,
                    _token: $('input[name=_token]').val()
                },
                success: function(data) {
                    $('.activityChk').each(function(index, el) {
                        if ($(el).is(':checked') == true) {
                            $(el).attr('disabled', true);
                            $(el).closest('td').siblings().find('button').attr('disabled', true);
                        }
                    });
                },
            });
        }

        function filterActivites(btn, color) {
            $('#filters-div').children().css("background-color", "").css("color", "slategray");
            $(btn).css("background-color", "#030303").css('color', 'white');
            $('#period').val($(btn).attr('data-period'));
            $('.activities-datatable').DataTable().ajax.reload();
        }

        function activityEdit(el) {
            var id = $(el).attr('data-record-id');
            var type = $(el).attr('data-type');
            var quote_uuid = $(el).attr('data-quote-uuid');
            $.ajax({
                url: '/activities/getEditView',
                method: "POST",
                data: {
                    activity_id: id,
                    quoteType: type,
                    quote_uuid: quote_uuid,
                    _token: $('input[name=_token]').val()
                },
                success: function(data) {
                    $('#activityEditModalContent').html(data);
                    $('#activityEditModal').modal('show');
                },
            });
        }
        function getQuoteTypeCode(id){
            switch (id) {
                case 1:
                    return 'CAR-';
                case 2:
                    return 'HOM-';
                case 3:
                    return 'HEA-';
                case 4:
                    return 'LIF-';
                case 5:
                    return 'BUS-';
                case 6:
                    return 'BIK-';
                case 7:
                    return 'YAC-';
                case 8:
                    return 'TRA-';

            }
        }
        function getQuoteTypeById(id) {
            switch (id) {
                case 1:
                    return 'car';
                case 2:
                    return 'home';
                case 3:
                    return 'health';
                case 4:
                    return 'life';
                case 5:
                    return 'business';
                case 6:
                    return 'bike';
                case 7:
                    return 'yacht';
                case 8:
                    return 'travel';

            }
        }

        function isActivityFormValid() {
            var isValid = true;
            if ($('#title').val() == '') {
                $('#title').next('span').html('Title is required').delay(5000).hide(0);;
                isValid = false;
            }
            if ($('#description').val() == '') {
                $('#description').next('span').html('Description is required').delay(5000).hide(0);;
                isValid = false;
            }
            if ($('#due_date').val() == '') {
                $('#due_date').next('span').html('Due Date is required').delay(5000).hide(0);;
                isValid = false;
            }
            if ($('#assignee_id').val() == '') {
                $('#assignee_id').next('span').html('Assignee is required').delay(5000).hide(0);;
                isValid = false;
            }
            return isValid;
        }

        function deleteActivity(el) {
            if(confirm('Are you sure you want to delete this activity?')) {
            var id = $(el).attr('data-record-id');
            var quote_uuid = $(el).attr('data-quote-uuid');
            var type = $(el).attr('data-type');
            $.ajax({
                url: '/activities/' + id + '/delete',
                method: "POST",
                data: {
                    quote_uuid: quote_uuid,
                    _token: $('input[name=_token]').val(),
                    quoteType: type,
                },
                success: function(data) {
                    window.location.reload();
                },
            });
        }
        }

        function submitUpdateActivity(el) {
            var uuid = $(el).attr('data-record-id');
            if (isActivityFormValid()) {
                var id = $(el).attr('data-record-id');
                var type = $(el).attr('data-type');
                var quote_uuid = $(el).attr('data-quote-uuid');
                $.ajax({
                    url: '/activities/' + uuid + '/update',
                    method: "POST",
                    data: {
                        title: $('#title').val(),
                        description: $('#description').val(),
                        due_date: $('#due_date').val(),
                        assignee_id: $('#assignee_id').val(),
                        _token: $('input[name=_token]').val()
                    },
                    success: function(data) {
                        $('#activityEditModal').modal('hide');
                        $('#sucess-div').text('Activity updated successfully').show().delay(5000).hide(0);
                    },
                });
            } else return false;

        }
        function OldformatedDate(date) {
            var newDate = new Date(date);
            var offset = newDate.getTimezoneOffset();
            newDate = new Date(newDate.getTime() - (offset*60*1000));
            newDate = newDate.toISOString().split('T')[0];
            return newDate;
        }

        function addActivity() {
            $('#activityModal').modal('show');
        }
        function padTo2Digits(num) {
        return num.toString().padStart(2, '0');
        }

        function NewformatDate(date) {
        return (
            [
            date.getFullYear(),
            padTo2Digits(date.getMonth() + 1),
            padTo2Digits(date.getDate()),
            ].join('-') +
            ' ' +
            [
            padTo2Digits(date.getHours()),
            padTo2Digits(date.getMinutes()),
            padTo2Digits(date.getSeconds()),
            ].join(':')
        );
        }
        $(document).ready(function() {
            $("#hiddenField").daterangepicker({
                timePicker: true,
                singleDatePicker: false,
                timePicker24Hour: true,
                locale: {
                    format: 'YYYY-MM-DD HH:mm:ss'
                }
            });
            $('#search-activities-reset').on('click', function (){
                $('#customPeriodStart').val('');
                $('#customPeriodEnd').val('');
                $('#assignee_id').val('');
                $('#today').click();
            });
            $('#hiddenField').on('apply.daterangepicker', function(ev, picker) {
                var startDate = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
                var endDate = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
               
                $('#customPeriodStart').val(startDate);
                $('#customPeriodEnd').val(endDate);
                $('#period').val('custom');
                $('#filters-div').children().css("background-color", "").css("color", "slategray");
                $('#hiddenField').css("background-color", "#030303").css('color', 'white');
                $('.activities-datatable').DataTable().ajax.reload();
            });
            
            var activitiesTable = $(".activities-datatable").DataTable({
                ordering: false,
                info: false,
                searching: false,
                bLengthChange: false,
                serverSide: true,
                ajax: {
                    url: config.routes.activitiesDataTable,
                    data: function(d) {
                        d.period = $("#period").val();
                        d.assignee_id = $("#assignee_id").val();
                        d.startDate = $("#customPeriodStart").val();
                        d.endDate = $("#customPeriodEnd").val();
                        d.status = $("#status").val();
                    }
                },
                columns: [

                    {
                        data: "title",
                        name: "title"
                    },
                    {
                        data: 'quote_request_id',
                        name: 'quote_request_id',
                        render: function(data, type, row) {
                            
                            var url = '/quotes/' + getQuoteTypeById(row.quote_type_id) + '/' + row
                                .quote_uuid;
                            if (row.quote_uuid) {
                                var quoteTypeCode = getQuoteTypeCode(row.quote_type_id);
                                var CDBID = quoteTypeCode + '-' + row.quote_uuid.toUpperCase();
                                return "<a target='_blank' href='" + url + "'>" + CDBID +
                                    "</a>";
                            } else {
                                return '';
                            }
                        }
                    },
                    {
                        data: "client_name",
                        name: "client_name"
                    },
                    {
                        data: 'due_date',
                        name: 'due_date'
                    },
                    {
                        data: "assignee_name",
                        name: "assignee_name"
                    },
                    {
                        data: 'uuid',
                        name: 'uuid',
                        render: function(data, type, row) {
                            var ischecked = row.status == 1 ? `checked="checked"` : '';
                            var checkbox = `
                    <label class="custom-checkbox"><input type="checkbox" ` + ischecked +
                                ` class="activityChk" onclick="updateStatus('` + row.id +
                                `, this')" name="activityChk" value="` + row.id + `">
                    <span class="checkbox"></span>
                    </label>`;
                            var url = '/activities/' + row.uuid;
                            return checkbox;
                        }
                    },
                    {
                        data: 'id',
                        name: 'id',
                        render: function(data, type, row) {
                            var quote_type = getQuoteTypeById(row.quote_type_id)
                            return "<button class='btn btn-sm btn-warning edit-activity-btn' data-type='" +
                                quote_type + "'  data-quote-uuid='" + row.quote_uuid +
                                "' data-record-id='" + row.id +
                                "' id='edit-activity-btn' onclick='activityEdit(this)'>Edit</button><button onclick='deleteActivity(this)' class='btn btn-warning btn-sm'  data-type='" +
                                quote_type + "'  data-quote-uuid='" + row.quote_uuid +
                                "' data-record-id='" + row.id +
                                "'>Delete</button>"
                        }
                    },
                ],
                drawCallback: function (settings) {
                    $("#totalActivites").text("Total Activites: " + settings._iRecordsTotal);
                }
            });
            activitiesTable.on('draw', function() {
                var rows = $('.activities-datatable tr');
                var headerRowColumns = $(rows[0]).children();
                var nextFollowupDateColumn = 0;
                for (let i = 0; i < headerRowColumns.length; i++) {
                    const element = headerRowColumns[i];
                    if(element.outerText == "Followup Date"){
                        nextFollowupDateColumn = i;
                    }
                }
                for (let index = 1; index < rows.length; index++) {
                    var columns = $(rows[index]).children();
                    for (let i = 0; i < columns.length; i++) {
                        if(i == nextFollowupDateColumn && $(columns[i]).text() != ""){
                            if( NewformatDate(new Date()) > $(columns[i]).text() ) {
                                $(rows[index]).children().eq(i).css({'color': 'white', 'background-color': 'red', 'font-weight': 'bold', 'font-size': '12px'});
                            }
                        }
                    }
                }
                $('.activityChk').each(function(index, el) {
                    if ($(el).is(':checked') == true) {
                        $(el).attr('disabled', true);
                        $(el).closest('td').siblings().find('button').attr('disabled', true);
                    }
                });

            });
            $('#search-activities-submit').on('click', function () {
                activitiesTable.draw();
            });
        });
    </script>
    <div class="row">
        <div class="x_panel">
            <div class="x_title">
                <h2>Search Activity</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a id="add-activity-btn" href="javascript:addActivity()" class="btn btn-warning btn-sm">Create
                            Activity</a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                @if (session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                @if (session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="row">
                        <div class="x_panel">
                            @foreach($errors->all() as $error)
                                <div class="alert alert-danger">{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <form method="POST" id="search-activities" action="" class="form-horizontal form-label-left" role="form"
                    data-parsley-validate="" novalidate="">
                    @method('POST')
                    {{ csrf_field() }}
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" id="vehicle_type_label"
                                for="Payment mode">Assigned To</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <select class="form-control" @iF(Auth::user()->isAdvisor()) disabled="disabled" @endif name="assignee_id" id="assignee_id">
                                        <option value="">Select Assigned To</option>
                                        @foreach ($advisors as $advisor)
                                            <option value="{{ $advisor['id'] }}">{{ $advisor['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" id="vehicle_type_label"
                            for="Payment mode">Status</label>
                        <div class="col-md-6 col-sm-6">
                            <div class="input-group">
                                <select class="form-control" name="status" id="status">
                                    <option value="">Select Activity Status</option>
                                    <option value="1">Done</option>
                                    <option value="0">Pending</option>
                                </select>
                            </div>
                        </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                        </div>
                        <div class="col">
                            <ul class="nav navbar-right panel_toolbox">
                                <li><input type="button" id="search-activities-reset" value="Reset"
                                        class="btn btn-success btn-sm"></li>
                                <li><input type="button" id="search-activities-submit" value="Search" class="btn btn-warning btn-sm"></li>
                                <input type="hidden" name="period" id="period" value="today">
                                <input type="hidden" name="customPeriodStart" id="customPeriodStart" >
                                <input type="hidden" name="customPeriodStart" id="customPeriodEnd" >
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="x_panel">
            <div class="x_title">
                <h2>Activities</h2>
                <div class="" id="filters-div" style="float: right;">
                    <button data-period="overdue" onclick="javascript:filterActivites(this)">Overdue</button><button
                        data-period="today" style="background-color: #030303; color: white;" id="today"
                        onclick="javascript:filterActivites(this)">Today</button><button data-period="tomorrow"
                        onclick="javascript:filterActivites(this)">Tomorrow</button><button data-period="this_week"
                        onclick="javascript:filterActivites(this)">This Week</button><button data-period="this_month"
                        onclick="javascript:filterActivites(this)">This Month</button>
                    <button type="button" id="hiddenField" value="Custom" class="datepicker">Custom</button>

                </div>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="alert alert-success" style="display: none" id="sucess-div"></div>
                <table class="table table-striped jambo_table activities-datatable" style="width:100%">
                    <label id="totalActivites" style="font-weight: bold;margin-left: 18px;"></label>
                    <thead>
                        <tr>

                            <th style="width: 15%;">Title</th>
                            <th style="width: 15%;">CDBID</th>
                            <th style="width: 15%;">Client Name</th>
                            <th style="width: 15%;">Followup Date</th>
                            <th style="width: 15%;">Assigned To</th>
                            <th style="width: 5%;">Done</th>
                            <th style="width: 15%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <div class="modal fade" id="activityEditModal" name="activityEditModal" tabindex="-1" role="dialog"
        aria-labelledby="activityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">

            <div class="modal-content" id="activityEditModalContent">

            </div>

        </div>
    </div>

    <div class="modal fade" id="activityModal" name="activityModal" tabindex="-1" role="dialog"
        aria-labelledby="activityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">

            <div class="modal-content">
                <form method="post" action="/activities/createActivity" autocomplete="off">
                    {{ csrf_field() }}
                    @method('POST')
                    <input type="hidden" name="isActivityView" value="1" id="quote_uuid">
                    <div class="modal-header">
                        <h5 class="modal-title" id="duplicateLeadModalLabel" style="font-size: 16px !important;">
                            <i class="fa fa-cog" aria-hidden="true"></i>
                            <strong style="margin-left: 13px;">New Activity</strong>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="col-md-12" id="followup-div">
                            <div class="col">
                                <div class="input-group">
                                    <input id="email" type="text" class="form-control" name="title" value="{{ old('title') ?? '' }}" placeholder="Title" />
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group">
                                    <textarea placeholder="Description" class="form-control" id="description" rows="5" name="description"></textarea>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
                                    <input id="due_date" type="text" class="form-control" name="due_date"
                                        placeholder="Due Date" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="justify-content: center;">
                        <button type="submit" class="btn btn-sm btn-success">Add Activity</button>
                    </div>
                </form>
            </div>

        </div>
    </div>

@endsection
