@extends('layouts.app')
@section('title', 'View AMT')
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
    $(document).ready(function() {
        var amtDataTable = $(".amt-data-table").DataTable({
            ordering: false,
            info: true,
            searching: false,
            bLengthChange: false,
            serverSide: true,
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
            columns: [{
                data: 'code',
                name: 'code',
                render: function (data, type, row) {
                    var type = row.code && row.code.indexOf('HEA-') > -1 ? 'health' : 'business';
                    var href = '/quotes/' + type + '/' + row.uuid;
                    return "<a href='" + href + "'>" + row.code + "</a>";
                }
            },
            { data: "first_name", name: "first_name" },
            { data: "last_name", name: "last_name" },
            { data: "leadStatus", name: "leadStatus" },
            { data: "advisor_id_text", name: "advisor_id_text" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },

            ],
        });
        $('#amt_sbmt').on('click', function(e) {
            e.preventDefault();
            amtDataTable.draw();
        });
        $("#amt_reset").click(function() {
            $(this).closest('form').trigger('reset');
            amtDataTable.draw();
        });
    });
</script>
    <div class="row">
        <div class="x_panel">
            <div class="x_title">
                <h2>AMT Leads</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
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
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="searchfield">EMAIL</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="email" id="email" >
                                </div>
                            </div>
                        </div>
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
                    </div>
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
                <table class="table table-striped jambo_table amt-data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>CDB ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Lead Status</th>
                            <th>Assigned To</th>
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
@endsection
