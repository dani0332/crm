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
    const dateOptions = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    };
    let columns = [
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
                var statusText = getStatusText(data);
                if (data == 1) {
                    var html = `
                    <span class="status-text">${statusText}</span><label class="switch">
                                <input data-toggle="toggle"  data-size="lg" type="checkbox" data-id="${row.id}" data-userId="${row.userId}" checked="checked" class="chk success" id="is_active" name="is_active">
                                <span class="slider round"></span>
                            </label>`;

                    return html;
                } else {
                    var html = `<span class="status-text">${statusText}</span><label class="switch ">
                                                <input type="checkbox" data-id="${row.id}" data-userId="${row.userId}" class="chk danger" id="is_active" name="is_active">
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
                var statusText = getStatusText(data);
                return '<span class="status-text">'+ statusText + '</span>';
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
    columns.push({
        data: 'reset_cap',
        name: 'reset_cap',
        orderable: false,
        searchable: false,
        render: function(data, type, row) {
            var statusText = getStatusText(data);
            if (data == 1) {
                var html = `
                <label class="switch">
                    <input data-toggle="toggle"  data-size="lg" type="checkbox" data-id="${row.id}" data-userId="${row.userId}" checked="checked" class="success reset_cap_toggle">
                    <span class="slider round"></span>
                </label>`;

                return html;
            } else {
                var html = `<label class="switch ">
                                <input type="checkbox" data-id="${row.id}" data-userId="${row.userId}" class="danger reset_cap_toggle">
                                <span class="slider round"></span>
                            </label>`;
                return html;
            }
        },
    })
    @endif

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
            if(refreshSwitch &&  refreshSwitch.length > 0){
                refreshSwitch.addEventListener('change', function() {
                    if (refreshSwitch.checked) {
                        enableRefresh();
                    } else {
                        disableRefresh();
                    }
                });

                enableRefresh();
            }

            $(document).on("change", "input:checkbox.reset_cap_toggle", function() {
                debugger;
                var ischecked = $(this).is(':checked');
                var self = $(this);
                $.ajax({
                    url: '/lead-allocation/toggle-reset-cap',
                    type: 'POST',
                    data: {
                        'userId': $(this).data('userid'),
                        '_token': $('meta[name="csrf-token"]').attr('content'),
                        'resetCap': ischecked ? 1 : 0
                    },
                    success: function(data) {
                        console.log('cap reset changed to '+ ischecked);
                        $('.loading').hide();
                    }
                });
            });


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
                                url: '/lead-allocation/update-cap',
                                type: 'POST',
                                data: data,
                                success: function(data) {
                                    window.location.reload(1);
                                }
                            });
                    }
                });
                @if(auth()->user()->hasAnyRole([RolesEnum::LeadPool]))
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
                                var userId = $(this).next().find('input').attr('data-userId');
                                var id = $(this).next().find('input').attr('data-id');
                                var originalMaxCap = fetchOriginalCap(userId);
                                if(originalMaxCap != maxCap){
                                    maxCapKeyValue.push({
                                        'userId' : $(this).next().find('input').attr('data-userId'),
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
                @endif

        });
        function changeAvailabilityInputs(ischecked){
            $inputs = $('.chk');
            $inputs.each(function(){
                ischecked ? $(this).attr('disabled', false) : $(this).attr('disabled', true);
            });
        }
        $(document).on("change", "input:checkbox.chk", function() {
            var ischecked = $(this).is(':checked');
            var self = $(this);
            if(ischecked) {
                $('#availableUsers').text(parseInt($('#availableUsers').text())+1);
                $('#UnavailableUsers').text(parseInt($('#UnavailableUsers').text())-1);
                $(this).closest('tr').find('.status-text').text('Online');
                $(this).removeClass('danger');
                $(this).addClass('success');
                var userId = $(self).data('userid');
                var allocationId = $(self).data('id');
                $.ajax({
                        url: '/lead-allocation/update-availability',
                        type: 'POST',
                        data: {
                            'userId': userId,
                            'id': allocationId,
                            'reason': 1,
                            '_token': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(data) {
                            console.log('availiblity changed');
                            $('.loading').hide();
                        }
                    });
            }
            else{
                $('#unavailableModal').modal('show'); // Show the modal
                $('#doneButton').click(function() {
                    var selectedReasonId = $('#unavailabilityReason').val();
                    var selectedReasonText = $('#unavailabilityReason').find(':selected').data('text');
                    $('#availableUsers').text(parseInt($('#availableUsers').text()) - 1);
                    $('#UnavailableUsers').text(parseInt($('#UnavailableUsers').text()) +  1);
                    $(self).closest('tr').find('.status-text').text(selectedReasonText);
                    $(this).removeClass('success');
                    $(this).addClass('danger');
                    var userId = $(self).data('userid');
                    var allocationId = $(self).data('id');
                    debugger;
                    $.ajax({
                        url: '/lead-allocation/update-availability',
                        type: 'POST',
                        data: {
                            'userId': userId,
                            'id': allocationId,
                            'reason': selectedReasonId,
                            '_token': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(data) {
                            $('.loading').hide();
                        }
                    });
                    $('#unavailableModal').modal('hide');
                });
                $('#closeButton').click(function() {
                    $(self).prop('checked', true);
                    $('#unavailableModal').modal('hide');
                });

            }
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
                        style="border-radius: 10px;float: left;border-left: 3px solid #facb19; margin-left: 70px;margin-bottom: 50px;font-size: 26px;background: whitesmoke;width: 250px;height: 120px;padding-left: 15px;padding-top: 18px;text-align:center;">
                        <span style="font-size: 21px">Available / UnAvailable</span>
                        <br />
                        <b><span style="color: black;"><label id="availableUsers">{{$availableUsers}} </label> /
                                <label id="UnavailableUsers">{{$unAvailableUsers}}</label></span></b>
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
                <table class="table table-striped table-sm jambo_table car_lead_allocation_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th style="width: 200px;">Tiers</th>
                            <th>Quads</th>
                            <th>Total Assigned</th>
                            <th>Manual Assigned</th>
                            <th >Auto Assigned</th>
                            <th >Max Cap Limit <i class="fa fa-info-circle" id="tooltip"
                                    data-toggle="tooltip" data-placement="top"
                                    title="For Unlimited Capactiy Add ( -1 )"></i>
                            </th>
                            <th>Status</th>
                            @if(auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::CarDeputyManager,
                            RolesEnum::LeadPool]))
                            <th>Last Login</th>
                            <th >Reset Cap</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Modal -->
<div class="modal fade" id="unavailableModal" tabindex="-1" role="dialog" aria-labelledby="unavailableModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unavailableModalLabel">Select Reason of Unavailability</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <select id="unavailabilityReason" class="form-control">
                    <option value="3" data-text="Unavailable">Temp. Unavailable</option>
                    <option value="4" data-text="Sick">Sick</option>
                    <option value="5" data-text="On Leave">On Leave</option>
                    <!-- Add more options as needed -->
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="closeButton" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="doneButton">Done</button>
            </div>
        </div>
    </div>
</div>

</div>
@endsection
