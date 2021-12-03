@extends('layouts.app')
@section('title', 'Lead Assignment')
@section('content')
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script>
        var userId = JSON.parse('<?php echo json_encode(Auth::user()->id); ?>');
        var id = JSON.parse('<?php echo json_encode(Auth::user()->getTeamUserIds()); ?>');
        $(document).ready(function() {
            var leadsAssignmentTable = $(".leadSearch-data-table").DataTable({
                ordering: false,
                info: false,
                searching: false,
                bLengthChange: false,
                serverSide: true,
                ajax: {
                    url: config.routes.leadassignmentDataTable,
                    data: function(d) {
                        d.startDate = $("#startDate").val();
                        d.endDate = $("#endDate").val();
                        d.assignedToId = $("#assignedToId").val();
                        d.leadType = $("#leadType").val();
                    },
                },
                drawCallback: function(settings) {
                    $(".loader").hide();
                },
                columns: [{
                        data: "id",
                        name: "id",
                        render: function(data, type, row, meta) {
                            return (
                                '<input type="checkbox" id="leadId" class="tmleadCheckbox" name="leadId" value="' +
                                data + '">'
                            );
                        },
                    }, {
                        data: 'id',
                        name: 'id',
                        render: function(data, type, row) {
                            return row.id
                        }
                    },
                    {
                        data: "created_at",
                        name: "created_at"
                    },
                    {
                        data: "first_name",
                        name: "first_name"
                    },
                    {
                        data: "last_name",
                        name: "last_name"
                    },
                    {
                        data: "advisor_name",
                        name: "advisor_name"
                    },
                    {
                        data: "lead_type",
                        name: "lead_type"
                    },
                ],
            });

            $("#leadsAssignmentSearch").submit(function(e) {
                e.preventDefault();
                $(".loader").show();
                if ($("#startDate").val() != "" || $("#endDate").val() != "") {
                    if ($("#startDate").val() != "" && $("#endDate").val() == "") {
                        $(".loader").hide();
                        alert("Please select start date and end date");
                        return false;
                    }
                    if ($("#startDate").val() == "" && $("#endDate").val() != "") {
                        $(".loader").hide();
                        alert("Please select start date and end date");
                        return false;
                    }
                }
                leadsAssignmentTable.draw();
            });

            $("#checkAll").click(function() {
                if ($(this).is(":checked")) {
                    $("#tm-leads-assign-div").show(300);
                    $('input:checkbox').not(this).prop('checked', this.checked);
                } else {
                    $("#tm-leads-assign-div").hide(200);
                    $('input:checkbox').not(this).prop('checked', this.checked);
                }
            });
            $("#resetBtn").click(function() {
                window.location.reload();
            });
            $("#assignToUser").click(function() {
                var leadIds = [];
                $.each($("input[name='leadId']:checked"), function() {
                    leadIds.push($(this).val() +
                        '|' + $(this)
                        .parent().parent()
                        .children().last().text());
                });
                $('#selectTmLeadId').val(leadIds);
                if (leadIds.length > 0) {
                    $.ajax({
                        url: '/manualLeadAssign',
                        type: "post",
                        data: {
                            assigned_to_id_new: $('#assigned_to_id_new').val(),
                            selectTmLeadId: $('#selectTmLeadId').val(),
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            $('.x_content').prepend('<div class="alert alert-success">' +
                                response + '</div>');
                            $('#tm-leads-assign-div').hide();
                            $(".loader").show();
                            leadsAssignmentTable.draw();
                            setTimeout(() => {
                                $(".alert-success").remove();
                            }, 6000);
                        },
                    });
                }

            });
            $(document).on("change", "#leadId", function() {
                var countSelectedTmLeadIds = document.querySelectorAll('#leadId:checked').length;
                if (countSelectedTmLeadIds > 0) {
                    $("#tm-leads-assign-div").show(300);
                } else {
                    $("#tm-leads-assign-div").hide(300);
                }
            });
            $("#tm-leads-assign-div").hide();
            $('#leadType').on('change', function() {
                $("#tm-leads-assign-div").hide(300);
                $(".loader").show();
                leadsAssignmentTable.draw();
                $.ajax({
                    url: '/getAdvisors?type=' + $("#leadType").val(),
                    type: "get",
                    success: function(response) {
                        $('#assigned_to_id_new').find('option').remove();
                        for (let index = 0; index < response.length; index++) {
                            const element = response[index];
                            $('#assigned_to_id_new').append($('<option>', {
                                value: element.id,
                                text: element.name
                            }));
                        }
                    },
                });
                setTimeout(() => {
                    $(".loader").hide();
                }, 1000);
            })
        });
    </script>
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Lead Assignment</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif

                    <form method="POST" id="leadsAssignmentSearch" class="form-horizontal form-label-left" role="form"
                        data-parsley-validate="" novalidate="" autocomplete="off">
                        {{ csrf_field() }}
                        @method('POST')
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="Search By">Created Start Date</label>
                                <div class="col-md-6 col-sm-6">
                                    <input type="date" class="form-control" id="startDate"
                                        value="{{ old('startDate') }}" name="startDate">
                                </div>
                            </div>
                            <div class="col">
                                <div>
                                    <label class="col-form-label col-md-2 col-sm-2" for="Search Value">Created Stop
                                        Date</label>
                                    <div class="col-md-6 col-sm-6">
                                        <input type="date" class="form-control" id="endDate" value={{ old('endDate') }}
                                            name="endDate">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">

                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="Start Date">Lead Assigned To</label>
                                <div class="col-md-6 col-sm-6">
                                    <select class="form-control" id="assignedToId" name="assignedToId">
                                        <option value="">Please Select Lead Assigned To</option>
                                        @foreach ($assignToUsers as $item)
                                            @if (old('assignedToId') == $item->name)
                                                <option value="{{ $item->name }}" selected>{{ $item->name }}</option>
                                            @else
                                                <option value="{{ $item->name }}">{{ $item->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="Start Date">Insurance Type<span
                                        class="required">*</span></label>
                                <div class="col-md-6 col-sm-6">
                                    <select class="form-control" id="leadType" name="leadType">
                                        <option value="">Please Select Insurance Type</option>
                                        @foreach ($insuranceTypes as $item)
                                            <option value="{{ $item }}">{{ $item }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">

                            </div>
                            <div class="col">
                                <ul class="nav navbar-right panel_toolbox">
                                    <li><input type="submit" class="btn btn-warning btn-sm" value="Search"></li>
                                    <li><input id="resetBtn" type="reset" class="btn btn-warning btn-sm"></li>
                                </ul>
                            </div>
                        </div>
                    </form>

                    <form method="post" action={{ route('manualAssignment') }} class="form-horizontal form-label-left"
                        role="form" id="saveAssignUser" data-parsley-validate="" novalidate="" autocomplete="off">
                        {{ csrf_field() }}
                        @method('GET')
                        <div class="row" id="tm-leads-assign-div">
                            <div class="col-md-12 col-sm-12">
                                <div class="x_panel">
                                    <div class="x_title">
                                        <h2>Assign Leads</h2>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div id="form-to-show">
                                        <div class="item form-group">
                                            <label class="col-form-label col-md-2 col-sm-2" for="Assign To">Assign
                                                To</label>
                                            <div class="col-md-6 col-sm-6">
                                                <select class="form-control" id="assigned_to_id_new"
                                                    name="assigned_to_id_new">
                                                    @foreach ($advisors as $handler)
                                                        <option value="{{ $handler['uuid'] }}">{{ $handler['name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="item form-group">
                                            <label class="col-form-label col-md-2 col-sm-2" for="first-name"> </label>
                                            <div class="col-md-6 col-sm-6">
                                                <div class="input-group">
                                                    <button type="button" id="assignToUser" name="assignToUser"
                                                        class="btn btn-warning btn-sm">Assign</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="modelType" name="modelType" value="">
                        <input type="hidden" id="displayTmLeadsDownloadCsvIcon" name="displayTmLeadsDownloadCsvIcon"
                            value="">
                        <input type="hidden" id="selectTmLeadId" name="selectTmLeadId" value="">
                    </form>
                    <table class="table table-striped jambo_table leadSearch-data-table" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width: 15px;"><input type="checkbox" id="checkAll" name="checkAll" value=""></th>
                                <th>id</th>
                                <th>Created At</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Assigned To</th>
                                <th>Lead Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="odd">
                                <td valign="top" colspan="7" class="dataTables_empty">No data available in table</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
