@extends('layouts.app')
@section('title', 'My Leads')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script>
        function formatedDate(date) {
            var newDate = new Date(date);
            var offset = newDate.getTimezoneOffset();
            newDate = new Date(newDate.getTime() - (offset*60*1000));
            newDate = newDate.toISOString().split('T')[0];
            return newDate;
        }
        function changeIcon(item){
            $(item).find('i').toggleClass("fa-angle-double-down fa-angle-double-up");
        }
        var userId = JSON.parse('<?php echo json_encode(Auth::user()->id); ?>');
        var isAdmin = JSON.parse('<?php echo json_encode(Auth::user()->hasRole('ADMIN')); ?>');
        var teamUserIds = JSON.parse('<?php echo json_encode(Auth::user()->getTeamUserIds()); ?>');
        $(document).ready(function() {
            $('#collapseOne').collapse('hide');
            $('#handler').find('i').toggleClass("fa-angle-double-down fa-angle-double-up");
            $('#mylead-search-submit-btn').on('click', function (e){
                e.preventDefault();
                if($('#startedAt').val() != '' && $('#endAt').val() == '') {
                    $('#endAt').next().html('Please select assigned to end date');
                    return false;
                }
                if($('#startedAt').val() == '' && $('#endAt').val() != '') {
                    $('#startedAt').next().html('Please select assigned to start date');
                    return false;
                }
                if($('#nfdSart').val() != '' && $('#nfdEnd').val() == '') {
                    $('#nfdEnd').next().html('Please select next followup end date');
                    return false;
                }
                if($('#nfdSart').val() == '' && $('#nfdEnd').val() != '') {
                    $('#nfdSart').next().html('Please select next followup start date');
                    return false;
                }
                $("span").each(function (k, v) {
                    if($(v).hasClass('text-danger')){
                        $(v).html('');
                    }
                });
                $('#my-leads-form').submit();
            });
            
            var followupLeadsTable = $("#overDueFollowups").DataTable({
                ordering: true,
                info: false,
                searching: false,
                bLengthChange: false,
                serverSide: true,
                ajax: {
                    url: '/getoverdueleads',
                    data: function(d) {
                        d.teamName = $("#teamType").val();
                    },
                },
                columnDefs: [
                    { orderable: false, targets: [1,2,5,6,] }
                    ],
                columns: [{
                        data: 'id',
                        name: 'id',
                        render: function(data, type, row) {
                            return "<a target='_blank' href='/quotes/" + $("#modelType").val().toLowerCase() + '/' + row.uuid + "'>" + row.code + "</a>"
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
            var myleadsTable = $(".leadSearch-data-table").DataTable({
                ordering: true,
                info: false,
                searching: false,
                bLengthChange: false,
                serverSide: true,
                ajax: {
                    url: config.routes.myleadsDataTable,
                    data: function(d) {
                        d.leadType = $("#modelType").val();
                        d.cdbId = $("#cdbId").val();
                        d.leadStatus = $("#leadStatus").val();
                        d.startedAt = $("#startedAt").val();
                        d.endAt = $("#endAt").val();
                        d.nfdSart = $('#nfdSart').val();
                        d.nfdEnd = $('#nfdEnd').val();
                        d.email = $('#email').val();
                        d.teamType = $('#teamType').val();
                    },
                },
                columnDefs: [
                    { orderable: false, targets: [1,2,5,6,] }
                    ],
                columns: [{
                        data: 'id',
                        name: 'id',
                        render: function(data, type, row) {
                            return "<a target='_blank' href='/quotes/" + $("#modelType").val().toLowerCase() + '/' + row.uuid + "'>" + row.code + "</a>"
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
           
            followupLeadsTable.on( 'draw', function () {
                var rows = $('#overDueFollowups tr');
                var headerRowColumns = $(rows[0]).children();
                var nextFollowupDateColumn = 0;
                for (let i = 0; i < headerRowColumns.length; i++) {
                    const element = headerRowColumns[i];
                    if(element.outerText == "Next FollowUp Date"){
                        nextFollowupDateColumn = i;
                    }
                }
                for (let index = 1; index < rows.length; index++) {
                    var columns = $(rows[index]).children();
                    for (let i = 0; i < columns.length; i++) {
                        if(i == nextFollowupDateColumn && $(columns[i]).text() != ""){
                            if(formatedDate(new Date()) > formatedDate(new Date($(columns[i]).text()))){
                                $(rows[index]).children().eq(i).css({'color': 'white', 'background-color': 'red', 'font-weight': 'bold', 'font-size': '12px'});
                            }
                        }
                    }
                }
            } );
            myleadsTable.on('draw', function() {
                var rows = $('#dtBasicExample tr');
                var headerRowColumns = $(rows[0]).children();
                var nextFollowupDateColumn = 0;
                for (let i = 0; i < headerRowColumns.length; i++) {
                    const element = headerRowColumns[i];
                    if(element.outerText == "Next FollowUp Date"){
                        nextFollowupDateColumn = i;
                    }
                }
                for (let index = 1; index < rows.length; index++) {
                    var columns = $(rows[index]).children();
                    for (let i = 0; i < columns.length; i++) {
                        if(i == nextFollowupDateColumn && $(columns[i]).text() != ""){
                            if(formatedDate(new Date()) > formatedDate(new Date($(columns[i]).text()))){
                                $(rows[index]).children().eq(i).css({'color': 'white', 'background-color': 'red', 'font-weight': 'bold', 'font-size': '12px'});
                            }
                        }
                    }
                }
            });

            $('#mylead-reset-btn').on('click', function(){
                $("span").each(function (k, v) {
                    if($(v).hasClass('text-danger')){
                        $(v).html('');
                    }
                });
                $(':input', '#my-leads-form')
                    .not(':button, :submit, :reset, :hidden')
                    .val('')
                    .prop('checked', false)
                    .prop('selected', false);
                $(".loader").show();
                myleadsTable.draw();
                setTimeout(() => {
                    $(".loader").hide();
                }, 1000);
            });

            $("#my-leads-form").submit(function(e) {
                e.preventDefault();
                $(".loader").show();
                myleadsTable.draw();
                followupLeadsTable.draw();
                $(".loader").hide();
            });

            $(".toggle-btn").on("click", function() {
                $(".show-visual-cards").addClass("showme");
                $(".show-container").removeClass("showme");
                $(".show-container").addClass("hideme");
                $(this).addClass("active");
                $(".toggle-btn-2").removeClass("active");
            });
            $(".toggle-btn-2").on("click", function() {
                $(".show-visual-cards").addClass("hideme");
                $(".show-visual-cards").removeClass("showme");
                $(".show-container").addClass("showme");
                $(this).addClass("active");
                $(".toggle-btn").removeClass("active");
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

    </script>
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel" style="overflow:hidden">
                <div class="x_title">
                    <h2>My Leads</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                <button type="button" class="btn btn-warning btn-sm toggle-btn active float-right change-layout">Cards View</button>
                <button type="button" class="btn btn-warning btn-sm toggle-btn-2 float-right change-layout">List View</button>
                <div class="show-visual-cards showme">
                    <x-my-leads-visual-card
                        :teamName="$teamName"
                        :leadStatuses="$leadStatusList"
                    />
                </div>
                <div class="show-container hideme">
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
                        <input type="hidden" name="modelType" id="modelType" value="{{ $teamName }}">
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-4 col-sm-4" for="Start Date">Assigned Date Start</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="date" name="startedAt" id="startedAt" class="form-control">
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-4 col-sm-4" for="Start Date">Assigned Date End</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="date" name="endAt" id="endAt" class="form-control">
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-4 col-sm-4" for="Start Date">NextFollowup Date Start</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="date" name="nfdSart" id="nfdSart" class="form-control">
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-4 col-sm-4" for="End Date">NextFollowup Date End</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="date" name="nfdEnd" id="nfdEnd" class="form-control">
                                        <span class="text-danger"></span>
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
                                <label class="col-form-label col-md-4 col-sm-4">Email</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="text" name="email" id="email" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-4 col-sm-4" for="Start Date">Lead Type</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <select class="form-control" id="teamType" name="teamType">
                                            @foreach ($allowedTeamTypes as $item)
                                                <option @if($item['id'] == $parentTeamId) selected @endif value="{{$item['name']}}">{{$item['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div  class="col">
                            </div>
                            <div class="col">
                                <ul class="nav navbar-right panel_toolbox">
                                    <li><input type="submit" id="mylead-search-submit-btn" class="btn btn-warning btn-sm" value="Search"></li>
                                    <li><input type="reset" id="mylead-reset-btn" class="btn btn-warning btn-sm"></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                    <div id="accordion" style="width: 98%;margin-left: 20px;">
                        <div class="card">
                          <div class="card-header" id="headingOne" style="background-color: #4183BD;">
                            <h5 class="mb-0">
                              <a style="background-color: transparent;color: white;border: 0px;" onclick="javascript:changeIcon(this)" id="handler" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                <i class="fa fa-angle-double-up" aria-hidden="true"></i> Over Due Leads
                              </a>
                            </h5>
                          </div>
                        </div>
                    <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                        <div class="card-body" style="border: 1px solid #ced4da;margin-bottom: 25px;">
                            <table  id="overDueFollowups" class="table table-striped jambo_table" style="width:100%">
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
                    
                    <table  id="dtBasicExample" class="table table-striped jambo_table leadSearch-data-table" style="width:100%">
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
    </div>
@endsection
