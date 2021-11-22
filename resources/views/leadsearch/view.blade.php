@extends('layouts.app')
@section('title', 'Lead Search')
@section('content')
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script>
        var userId = JSON.parse('<?php echo json_encode(Auth::user()->id); ?>');
        var isAdmin = JSON.parse('<?php echo json_encode(Auth::user()->hasRole('ADMIN')); ?>');
        var teamUserIds = JSON.parse('<?php echo json_encode(Auth::user()->getTeamUserIds()); ?>');
        $(document).ready(function() {
            var searchLeadsTable = $(".leadSearch-data-table").DataTable({
                ordering: false,
                info: false,
                searching: false,
                bLengthChange: false,
                serverSide: true,
                ajax: {
                    url: config.routes.searchLeadsDataTable,
                    data: function(d) {
                        d.leadType = $("#leadType").val();
                        d.cdbID = $("#cdbID").val();
                        d.email = $("#email").val();
                        d.phnNumber = $("#phnNumber").val();
                    },
                },
                columns: [{
                        data: 'id',
                        name: 'id',
                        render: function(data, type, row) {
                            if (teamUserIds.includes(row.advisor_id) || row.advisor_id == userId ||
                                isAdmin) {
                                return "<a href='/quotes/" + $("#leadType").val() + "/" + row.uuid +
                                    "'>" + row.id + "</a>"
                            } else {
                                return "You don’t have access to view this lead"
                            }
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
                        data: "lead_status",
                        name: "lead_status"
                    },
                ],
            });
        });
    </script>
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Search Leads</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif

                    <form method="POST" id="search-leads" class="form-horizontal form-label-left" role="form"
                        data-parsley-validate="" novalidate="" autocomplete="off">
                        {{ csrf_field() }}
                        @method('POST')
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="Search By">CDB ID </label>
                                <div class="col-md-6 col-sm-6">
                                    <input type="text" class="form-control" id="cdbID" name="cdbID">
                                </div>
                            </div>
                            <div class="col">
                                <div>
                                    <label class="col-form-label col-md-2 col-sm-2" for="Search Value">Customer
                                        Email</label>
                                    <div class="col-md-6 col-sm-6">
                                        <input type="text" class="form-control" id="email" name="email">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="Start Date">Customer Phone
                                    Number</label>
                                <div class="col-md-6 col-sm-6">
                                    <input class="form-control" type="number" max="10" id="phnNumber" name="phnNumber">
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="Start Date">Lead Type <span
                                        class="required">*</span></label>
                                <div class="col-md-6 col-sm-6">
                                    <select class="form-control" id="leadType" name="leadType">
                                        <option value="">Please Select Lead Type</option>
                                        <option value="home">Home</option>
                                        <option value="health">Health</option>
                                        <option value="life">Life</option>
                                        <option value="business">Business</option>
                                        <option value="travel">Travel</option>
                                    </select>
                                    @if ($errors->has('leadType'))
                                        <span class="text-danger">{{ $errors->first('leadType') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">

                            </div>
                            <div class="col">
                                <ul class="nav navbar-right panel_toolbox">
                                    <li><input type="submit" class="btn btn-warning btn-sm" value="Search"></li>
                                    <li><input type="reset" class="btn btn-warning btn-sm"></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                    <table class="table table-striped jambo_table leadSearch-data-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>id</th>
                                <th>Created At</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Assigned To</th>
                                <th>Lead Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="odd">
                                <td valign="top" colspan="6" class="dataTables_empty">No data available in table</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
