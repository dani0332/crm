@extends('layouts.app')
@section('title', 'CAR Lead Allocation Management')
@section('content')
@php
use App\Enums\RolesEnum;
@endphp
<meta name="csrf-token" content="{{ csrf_token() }}" />
<link href="{{ asset('css/bootstrap-toggle.css') }}" rel="stylesheet">
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>

<script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>

<script>
    const dateOptions = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    };
    let columns = [{
        data: 'userId',
        name: 'userId',
        orderable: false,
        searchable: false
    },
        {
            data: 'userName',
            name: 'userName',
            orderable: true,
            searchable: false
        },
        {
            data: 'tiers',
            name: 'tiers',
            orderable: false,
            searchable: false,
        },
        {
            data: 'quads',
            name: 'quads',
            orderable: true,
            searchable: false
        },
        {
            data: 'allocationCount',
            name: 'allocationCount',
            orderable: false,
            searchable: false
        },
        {
            data: 'manualAllocationCount',
            name: 'manualAllocationCount',
            orderable: false,
            searchable: false
        },
        {
            data: 'autoAllocationCount',
            name: 'autoAllocationCount',
            orderable: false,
            searchable: false
        },
        {
            data: 'lastAllocation',
            name: 'lastAllocation',
            orderable: false,
            searchable: false,
        },
        {
            class: 'td-max-cap',
            data: 'maxCapacity',
            name: 'maxCapacity',
            orderable: false,
            searchable: false
        }
    ];

    @if(auth()->user()->hasRole(RolesEnum::LeadPool))
        columns.push({
            data: 'isAvailable',
            name: 'isAvailable',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                if (data == 1) {
                    var html = `
                    <span class="status-text">Available</span><label class="switch" style="margin-left: 20px;">
                                <input data-toggle="toggle"  data-size="lg" type="checkbox" data-id="${row.id}" data-aid="${row.userId}" checked="checked" class="chk success" id="is_active" name="is_active">
                                <span class="slider round"></span>
                            </label>`;

                    return html;
                } else {
                    var html = `<span class="status-text">UnAvailable</span><label class="switch " style="margin-left: 20px;">
                                                <input type="checkbox" data-id="${row.id}" data-aid="${row.userId}" class="chk danger" id="is_active" name="is_active">
                                                <span class="slider round"></span>
                                            </label>`;
                    return html;
                }
            },
        });
    @else
        columns.push({
            data: 'isAvailable',
            name: 'isAvailable',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                return '<span class="status-text">'+ ((data == 1) ? 'Available' : 'Unavailable') + '</span>';
            },
        });
    @endif

    @if(auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::CarDeputyManager, RolesEnum::LeadPool]))
    columns.push({
        data: 'lastLogin',
        name: 'lastLogin',
        orderable: false,
        searchable: false,
    })
    @endif

</script>

<script>
    var leadAllocationDataTable = null;
        var maxCapKeyValue = [];
        var userOriginalCaps = [];
        let refreshTimeout;

        function enableRefresh() {
            const refreshSwitch = document.getElementById('refresh-switch');
            if(!refreshSwitch.checked){
                refreshSwitch.checked = true;
            }
            refreshTimeout = setTimeout(function() {
                window.location.reload(1);
            }, 80000);
        }

        function disableRefresh() {
            const refreshSwitch = document.getElementById('refresh-switch');
            if(refreshSwitch.checked){
                refreshSwitch.checked = false;
            }
            clearTimeout(refreshTimeout);
        }

        function revertCap(userId, element)
        {
            var maxCap = fetchOriginalCap(userId);

            $(element).parent().removeAttr("style").html(maxCap);

            maxCapKeyValue = maxCapKeyValue.filter(function( obj ) {
                return parseInt(obj.userId) !== userId;
            });

            if(maxCapKeyValue.length == 0) {
                $('#submitBtn').hide();
                enableRefresh();
            }
        }

        function fetchOriginalCap(userId)
        {
            var result = userOriginalCaps.filter(obj => {
                return parseInt(obj.userId) === parseInt(userId)
            });

            return result[0].maxCap;
        }

        $(document).ready(function() {

            const refreshSwitch = document.getElementById('refresh-switch');

            refreshSwitch.addEventListener('change', function() {
                if (refreshSwitch.checked) {
                    enableRefresh();
                } else {
                    disableRefresh();
                }
            });

            enableRefresh();

            var isAutoAllocationWorking = JSON.parse('<?php echo json_encode($isAutoAllocationWorking); ?>');
            var indexLastColumn = $(".car_lead_allocation_table").find('tr')[0].cells.length-1;
            leadAllocationDataTable = $('.car_lead_allocation_table').DataTable({
                    info: true,
                    serverSide: true,
                    searching: false,
                    paging: false,
                    processing: true,
                    lengthChange: false,
                    ordering: true,
                    ajax: config.routes.car_lead_allocation_index_route,
                    columns: columns,
                    drawCallback: function (settings) {
                        $('.car_lead_allocation_table tr').each(function(){
                            $(this).find('td:last').attr('style', 'float:left;');
                        });
                        if(isAutoAllocationWorking == '0') {
                            $inputs = $('.chk');
                            $inputs.each(function(){ $(this).attr('disabled', true); });
                        }
                    },
                    initComplete :function( settings, json){
                        json.data.forEach(element => {
                            userOriginalCaps.push({
                                userId: element.userId,
                                maxCap: element.maxCapacity,
                            });
                        });
                    }
                });

                $('#submitBtn').on('click', function(){
                    if(maxCapKeyValue.length > 0){

                        var data = {
                                    'max_cap': maxCapKeyValue,
                                    '_token': $('meta[name="csrf-token"]').attr('content')
                                };
                            $.ajax({
                                url: '/lead-allocation/updateCaps',
                                type: 'POST',
                                data: data,
                                success: function(data) {
                                    window.location.reload(1);
                                }
                            });
                    }
                });

                $('body').on('dblclick', 'table:first td.td-max-cap', function() {
                        var maxCapValue = parseInt($(this).text());
                        if(maxCapValue !== NaN){
                            $(this).html(`<input type="text" class="form-control" value="${maxCapValue}" />`);
                        }else{
                            $(this).html(`<input type="text" class="form-control" value="0" />`);
                        }
                        $(this).focusout(function() {
                            var activeElement = $(this);
                            var maxCap = parseInt($(this).find('input').val());
                            if(!maxCap || maxCap == 0 ){
                                $(activeElement).html(maxCapValue);
                                if(maxCapKeyValue.length > 0 ) {
                                    disableRefresh();
                                }else{
                                    enableRefresh();
                                    $(activeElement).html(maxCapValue);
                                    $('#submitBtn').hide();
                                    $(activeElement).unbind('focusout');
                                }
                                return false;
                            } else {
                                var userId = $(this).next().find('input').attr('data-aid');
                                var id = $(this).next().find('input').attr('data-id');
                                var originalMaxCap = fetchOriginalCap(userId);
                                if(originalMaxCap != maxCap){
                                    maxCapKeyValue.push({
                                        'userId' : $(this).next().find('input').attr('data-aid'),
                                        'maxCap' : maxCap
                                    });
                                    disableRefresh();
                                    $('#submitBtn').show();
                                    $(activeElement).html('<label>'+maxCap+'</label><span style="margin-left:25px;" onclick="revertCap('+userId+', this)"><i class="fa fa-undo" aria-hidden="true"></i></span>');
                                    $(activeElement).attr('style', 'border: 2px solid #facb197d;color:back;font-weight:900;background-color:#facb197d;');
                                    $(activeElement).unbind('focusout');
                                }else{
                                    (activeElement).html(maxCapValue);
                                }

                            }
                        });
                    });
        });
        function changeAvailabilityInputs(ischecked){
            $inputs = $('.chk');
            $inputs.each(function(){
                ischecked ? $(this).attr('disabled', false) : $(this).attr('disabled', true);
            });
        }
        $(document).on("change", "input:checkbox.chk", function() {
            var ischecked = $(this).is(':checked');
            if(ischecked){
                $('#availableUsers').text(parseInt($('#availableUsers').text())+1);
                $('#UnavailableUsers').text(parseInt($('#UnavailableUsers').text())-1);
                $(this).closest('tr').find('.status-text').text('Available');
                $(this).removeClass('danger');
                $(this).addClass('success');
            }
            else{
                $('#availableUsers').text(parseInt($('#availableUsers').text()) - 1);
                $('#UnavailableUsers').text(parseInt($('#UnavailableUsers').text()) +  1);
                $(this).closest('tr').find('.status-text').text('UnAvailable');
                $(this).removeClass('success');
                $(this).addClass('danger');
            }
            $.ajax({
                url: '/lead-allocation/updateAvailability',
                type: 'POST',
                data: {
                    'aid': $(this).data('aid'),
                    'id': $(this).data('id'),
                    'is_available': ischecked ? 1 : 0,
                    '_token': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    console.log(data);
                    $('.loading').hide();
                }
            });
        });
        $(document).on("change", "input:checkbox.carLeadSwitch", function() {
            if(confirm("Are you sure you want to change Car Lead Allocation Status?")){
                var ischecked = $(this).is(':checked');
                $.ajax({
                    url: '/lead-allocation/toggle-car-lead-allocation-job-status',
                    type: 'POST',
                    data : {
                        '_token': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        changeAvailabilityInputs(ischecked);
                    }
                });
            }else{
                $('.carLeadSwitch').prop('checked', $('.carLeadSwitch').val() == 'on' ? true : false);
                return false;
            }
        });
        $(document).on("change", "input:checkbox.carRenewalLeadSwitch", function() {
            if(confirm("Are you sure you want to change Renewal Leads Assignment Status?")){
                var ischecked = $(this).is(':checked');
                $.ajax({
                    url: '/lead-allocation/toggle-renewal-car-lead-allocation-status',
                    type: 'POST',
                    data : {
                        '_token': $('meta[name="csrf-token"]').attr('content')
                    },
                });
            }
            else{
                $('.carRenewalLeadSwitch').prop('checked', $('.carRenewalLeadSwitch').val() == 'on' ? true : false);
                return false;
            }
        });
        $(document).on("change", "input:checkbox.carLeadFIFOSwitch", function() {
            if(confirm("Are you sure you want to change CAR LEAD PICKUP FIFO Status?")){
                var ischecked = $(this).is(':checked');
                $.ajax({
                    url: '/lead-allocation/toggle-car-lead-fetch-sequence',
                    type: 'POST',
                    data : {
                        '_token': $('meta[name="csrf-token"]').attr('content')
                    },
                });
            }
            else{
                $('.carLeadFIFOSwitch').prop('checked', $('.carLeadFIFOSwitch').val() == 'on' ? true : false);
                return false;
            }
        });
</script>
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">

                @if(Auth::user()->hasRole(RolesEnum::Admin))
                <h2>Car Lead Allocation Management</h2>
                <span class="status-text"></span>
                <label class="switch " style="margin-left: 20px;float: left;margin-top: 5px;">
                    <input type="checkbox" @if($isAutoAllocationWorking=='1' ) checked="checked" @endif
                        class="carLeadSwitch success" id="jobSwitch" name="jobSwitch">
                    <span class="slider round"></span>
                </label>
                @endif


                @if(Auth::user()->hasRole(RolesEnum::LeadPool) || Auth::user()->hasRole(RolesEnum::Admin))
                <h2 style="margin-left:  80px !important">Pickup Sequence : FIFO</h2>
                <span class="status-text"></span>
                <label class="switch " style="margin-left: 20px;float: left;margin-top: 5px;">
                    <input type="checkbox" @if($isFIFO=='1' ) checked="checked" @endif
                        class="carLeadFIFOSwitch success">
                    <span class="slider round"></span>
                </label>
                @endif

                @if(Auth::user()->hasRole(RolesEnum::LeadPool) || Auth::user()->hasRole(RolesEnum::Admin))
                <h2 style="margin-left:  80px !important">Auto Refresh : </h2>
                <span class="status-text"></span>
                <label class="switch" style="margin-left: 20px;float: left;margin-top: 5px;">
                    <input id="refresh-switch" type="checkbox" checked="checked" class="success">
                    <span class="slider round"></span>
                </label>
                @endif

                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if (session()->has('message'))
                <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                @if (session()->has('success'))
                <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <div class="col-md-12" style="margin-left:8px;">
                    <div class="col-md-3"
                        style="border-radius: 10px;float: left;border-left: 3px solid #A1C86B;margin-bottom: 50px;font-size: 26px;background: whitesmoke;width: 250px;height: 120px;padding-left: 15px;padding-top: 18px;text-align:center;">
                        <span style="font-size: 21px">Team </span>
                        <br />
                        <b><span style="color: black;">Car</span></b>
                    </div>
                    <div class="col-md-3"
                        style="border-radius: 10px;float: left;border-left: 3px solid #4183BD;    margin-left: 70px;margin-bottom: 50px;font-size: 26px;background: whitesmoke;width: 250px;height: 120px;padding-left: 15px;padding-top: 18px;text-align:center;">
                        <span style="font-size: 21px">Assigned Lead Count </span>
                        <br />
                        <b><span style="color: black;">{{$totalAssignedLeadCount}}</span></b>
                    </div>
                    <div class="col-md-3"
                        style="border-radius: 10px;float: left;border-left: 3px solid #3015ca;    margin-left: 70px;margin-bottom: 50px;font-size: 26px;background: whitesmoke;width: 250px;height: 120px;padding-left: 15px;padding-top: 18px;text-align:center;">
                        <span style="font-size: 21px">Total Advisors</span>
                        <br />
                        <b><span style="color: black;">{{ $unAvailableUsers + $availableUsers}}</span></b>
                    </div>
                    <div class="col-md-3"
                        style="border-radius: 10px;float: left;border-left: 3px solid #facb19; margin-left: 70px;margin-bottom: 50px;font-size: 26px;background: whitesmoke;width: 250px;height: 120px;padding-left: 15px;padding-top: 18px;text-align:center;">
                        <span style="font-size: 21px">Available / UnAvailable</span>
                        <br />
                        <b><span style="color: black;"><label id="availableUsers">{{$availableUsers}} </label> /
                                <label id="UnavailableUsers">{{$unAvailableUsers}}</label></span></b>
                    </div>
                </div>
                <div class="col-md-12" style="margin-left:8px;">
                    <div class="col-md-3"
                        style="border-radius: 10px;float: left;border-left: 3px solid #A1C86B;margin-bottom: 50px;font-size: 26px;background: whitesmoke;width: 250px;height: 120px;padding-left: 15px;padding-top: 18px;text-align:center;">
                        <span style="font-size: 21px">Total Leads Today </span>
                        <br />
                        <b><span style="color: black;">{{ $todayTotalLeadCount}}</span></b>
                    </div>
                    <div class="col-md-3"
                        style="border-radius: 10px;float: left;border-left: 3px solid #facb19; margin-left: 70px;margin-bottom: 50px;font-size: 26px;background: whitesmoke;width: 350px;height: 120px;padding-left: 15px;padding-top: 18px;text-align:center;">
                        <span style="font-size: 21px">Total UnAssigned Leads </span>
                        <br />
                        <b><span style="color: black;">{{ $todayTotalUnAssignedLeadCount}}</span></b>
                    </div>
                </div>
                <div>
                    <button id="submitBtn" style="display: none;float: right;margin-right: 18px;" type="button"
                        class="btn btn-success">Submit
                        Cap Changes</button>
                </div>
                <table class="table table-striped jambo_table car_lead_allocation_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>User Id</th>
                            <th>Name</th>
                            <th>Tiers</th>
                            <th>Quads</th>
                            <th>Total Assigned</th>
                            <th>Manual Assigned</th>
                            <th>Auto Assigned</th>
                            <th>Last Allocation</th>
                            <th>Max Cap Limit <i class="fa fa-info-circle" id="tooltip" data-toggle="tooltip"
                                    data-placement="top" title="For Unlimited Capactiy Add ( -1 )"></i>
                            </th>
                            <th>Status</th>
                            @if(auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::CarDeputyManager,
                            RolesEnum::LeadPool]))
                            <th>Last Login</th>
                            @endif
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
