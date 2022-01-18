@extends('layouts.app')
@section('title', 'My Leads')
@section('content')
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script>
        var userId = JSON.parse('<?php echo json_encode(Auth::user()->id); ?>');
        var isAdmin = JSON.parse('<?php echo json_encode(Auth::user()->hasRole('ADMIN')); ?>');
        var teamUserIds = JSON.parse('<?php echo json_encode(Auth::user()->getTeamUserIds()); ?>');
        $(document).ready(function() {
            $("#myLeadsType").prop("selectedIndex", 0);
            var myleadsTable = $(".leadSearch-data-table").DataTable({
                ordering: false,
                info: false,
                searching: false,
                bLengthChange: false,
                serverSide: true,
                ajax: {
                    url: config.routes.myleadsDataTable,
                    data: function(d) {
                        d.leadType = $("#myLeadsType").val();
                        d.cdbId = $("#cdbId").val();
                        d.leadStatus = $("#leadStatus").val();
                        d.startedAt = $("#startedAt").val();
                        d.endAt = $("#endAt").val();
                    },
                },
                columns: [{
                        data: 'id',
                        name: 'id',
                        render: function(data, type, row) {
                            return "<a href='/quotes/" + $("#myLeadsType").val().toLowerCase() + '/' + row.uuid + "'>" + row.code + "</a>"
                        }
                    },
                    {
                        data: "clientName",
                        name: "clientName"
                    },
                    {
                        data: "leadStatus",
                        name: "leadStatus"
                    },
                    {
                        data: "createdAt",
                        name: "createdAt"
                    },
                    {
                        data: "assignedDate",
                        name: "assignedDate"
                    },
                    {
                        data: "assignedBy",
                        name: "assignedBy"
                    },
                    {
                        data: 'leadSource',
                        name: 'leadSource'
                    },
                    {
                        data: 'nextFollowupDate',
                        name: 'nextFollowupDate',
                    },
                ],
            });

            $("#my-leads-form").submit(function(e) {
                e.preventDefault();
                $(".loader").show();
                myleadsTable.draw();
                $(".loader").hide();
            });
        });
    </script>
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>My Leads</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif

                    <form method="POST" id="my-leads-form" class="form-horizontal form-label-left" role="form"
                        data-parsley-validate="" novalidate="" autocomplete="off">
                        {{ csrf_field() }}
                        @method('POST')
                        <select class="form-control" style="display: none" id="myLeadsType" name="leadType">

                            @foreach ($leadTypes as $item)
                                <option  value="{{$item}}">{{$item}}</option>
                            @endforeach
                        </select>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-4 col-sm-4" for="Start Date">Assigned Date Start</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="date" name="startedAt" id="startedAt" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-4 col-sm-4" for="Start Date">Assigned Date End</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="date" name="endAt" id="endAt" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-4 col-sm-4" for="Start Date">CDB ID</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="text" name="cdbId" id="cdbId" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-4 col-sm-4" for="Start Date">Lead Status</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <select class="form-control" id="leadStatus" name="leadStatus">
                                            <option value="" selected="selected">Select Lead Status</option>
                                            @foreach ($leadStatusList as $item)
                                                <option  value="{{$item->id}}">{{$item->text}}</option>
                                            @endforeach
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
                                    <li><input type="submit" class="btn btn-warning btn-sm" value="Search"></li>
                                    <li><input type="reset" class="btn btn-warning btn-sm"></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                    <table class="table table-striped jambo_table leadSearch-data-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>CDB ID</th>
                                <th>Client Name</th>
                                <th>Lead Status</th>
                                <th>Created Date</th>
                                <th>Assigned Date</th>
                                <th>Assigned By</th>
                                <th>Lead Source</th>
                                <th>Next FollowUp Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="odd">
                                <td valign="top" colspan="8" class="dataTables_empty">No data available in table</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
