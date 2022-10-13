@extends('layouts.app')
@section('title', 'CAR Lead Allocation Management')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<link href="{{ asset('css/bootstrap-toggle.css') }}" rel="stylesheet">
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>

<script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>

<script>
    var leadAllocationDataTable = null;
    $(document).ready(function() {
        var isAutoAllocationWorking = JSON.parse('<?php echo json_encode($isAutoAllocationWorking); ?>');
        var indexLastColumn = $(".car_lead_allocation_table").find('tr')[0].cells.length-1;
        leadAllocationDataTable = $('.car_lead_allocation_table').DataTable({
                info: true,
                serverSide: true,
                searching: false,
                paging: true,
                processing: true,
                ordering: true,
                lengthChange: false,
                ajax: config.routes.car_lead_allocation_index_route,
                columns: [{
                        data: 'userId',
                        name: 'userId',
                        orderable: true,
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
                        orderable: true,
                        searchable: false
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
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'lastAllocation',
                        name: 'lastAllocation',
                        orderable: true,
                        searchable: false,
                        render: function(data, type, row) {
                            if (data == null) {
                                return '-';
                            } else {
                                return new Date(data * 1000).toLocaleString();
                            }
                        }
                    },
                    {
                        data: 'maxCapacity',
                        name: 'maxCapacity',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'isAvailable',
                        name: 'isAvailable',
                        orderable: true,
                        searchable: false,
                        render: function(data, type, row) {
                            if (data == 1) {
                                var html = `<span class="status-text">Available</span><label class="switch " style="margin-left: 20px;">
                                    <input type="checkbox" data-id="${row.id}" data-aid="${row.userId}" checked="checked" class="chk success" id="is_active" name="is_active">
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
                    },
                    {
                        data: 'lastLogin',
                        name: 'lastLogin',
                        orderable: true,
                        searchable: false,
                    },
                ],
                drawCallback: function (settings) {
                    $('.car_lead_allocation_table tr').each(function(){
                        $(this).find('td:last').attr('style', 'float:left;');
                    });
                    if(isAutoAllocationWorking == '0') {
                        $inputs = $('.chk');
                        $inputs.each(function(){ $(this).attr('disabled', true); });
                    }
                }
            });


            $('body').on('dblclick', 'table:first td:nth-last-child(3)', function() {
                var maxCapValue = parseInt($(this).text());
                if(maxCapValue !== NaN){
                    $(this).html(`<input type="text" class="form-control" value="${maxCapValue}" />`);
                }else{
                    $(this).html(`<input type="text" class="form-control" value="0" />`);
                }
                $(this).focusout(function() {
                    var activeElement = $(this);
                    var maxCap = parseInt($(this).find('input').val());
                    if(maxCap < -1 || maxCap == 0){
                        alert('Please enter valid value for max capacity');
                        leadAllocationDataTable.draw();
                        return false;
                    }else{
                        var aid = $(this).next().find('input').attr('data-aid');
                        var id = $(this).next().find('input').attr('data-id');
                        var data = {
                            'max_cap': maxCap,
                            'aid': aid,
                            'id': id,
                            '_token': $('meta[name="csrf-token"]').attr('content')
                        };
                        $.ajax({
                            url: '/lead-allocation/updateAvailability',
                            type: 'POST',
                            data: data,
                            success: function(data) {
                                $(activeElement).html(maxCap);
                            }
                        });
                    }

                });
                $(this).focus();
                $(this).blur(endEdition);
            });

            function endEdition() {
                var el = $(this);
                myTable.cell(el).invalidate().draw();
                el.attr('contenteditable', 'false');
                el.off('blur', endEdition);
            }
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
                    url: '/lead-allocation/setCarLeadAllocationJobStatus',
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
                    url: '/lead-allocation/setRenewalCarLeadAllocationStatus',
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
                    url: '/lead-allocation/setCarLeadFetchSequence',
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
                <h2>Car Lead Allocation Management</h2>
                @if(Auth::user()->isAdmin())
                <span class="status-text"></span>
                <label class="switch "
                    style="margin-left: 20px;float: left;margin-top: 5px;">
                    <input type="checkbox" @if($isAutoAllocationWorking=='1' ) checked="checked" @endif
                        class="carLeadSwitch success" id="jobSwitch" name="jobSwitch">
                    <span class="slider round"></span>
                </label>
                @endif
                <h2 style="margin-left:  80px !important">Run Renewal Logic</h2>
                @if(Auth::user()->isAdmin())
                <span class="status-text"></span>
                <label class="switch "
                    style="margin-left: 20px;float: left;margin-top: 5px;">
                    <input type="checkbox" @if($isRenewalLeadAllocationWorking=='1' ) checked="checked" @endif
                        class="carRenewalLeadSwitch success" id="jobSwitch" name="jobSwitch">
                    <span class="slider round"></span>
                </label>
                @endif

                <h2 style="margin-left:  80px !important">Pickup Sequence : FIFO</h2>
                @if(Auth::user()->isAdmin())
                <span class="status-text"></span>
                <label class="switch "
                    style="margin-left: 20px;float: left;margin-top: 5px;">
                    <input type="checkbox" @if($isFIFO=='1' ) checked="checked" @endif
                        class="carLeadFIFOSwitch success">
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
                        style="border-radius: 10px;float: left;border-left: 3px solid #A1C86B;margin-bottom: 50px;font-size: 26px;background: whitesmoke;width: 250px;height: 120px;padding-left: 15px;padding-top: 18px;">
                        <span style="font-size: 21px">Team </span>
                        <br />
                        <b><span style="color: black;">Car</span></b>
                    </div>
                    <div class="col-md-3"
                        style="border-radius: 10px;float: left;border-left: 3px solid #4183BD;    margin-left: 70px;margin-bottom: 50px;font-size: 26px;background: whitesmoke;width: 250px;height: 120px;padding-left: 15px;padding-top: 18px;">
                        <span style="font-size: 21px">Assigned Lead Count </span>
                        <br />
                        <b><span style="color: black;">{{$totalAssignedLeadCount}}</span></b>
                    </div>
                    <div class="col-md-3"
                        style="border-radius: 10px;float: left;border-left: 3px solid #3015ca;    margin-left: 70px;margin-bottom: 50px;font-size: 26px;background: whitesmoke;width: 250px;height: 120px;padding-left: 15px;padding-top: 18px;">
                        <span style="font-size: 21px">Total Advisors</span>
                        <br />
                        <b><span style="color: black;">{{ $unAvailableUsers + $availableUsers}}</span></b>
                    </div>
                    <div class="col-md-3"
                        style="border-radius: 10px;float: left;border-left: 3px solid #facb19; margin-left: 70px;margin-bottom: 50px;font-size: 26px;background: whitesmoke;width: 250px;height: 120px;padding-left: 15px;padding-top: 18px;">
                        <span style="font-size: 21px">Availabe / UnAvailable</span>
                        <br />
                        <b><span style="color: black;"><label id="availableUsers">{{$availableUsers}} </label> /
                                <label id="UnavailableUsers">{{$unAvailableUsers}}</label></span></b>
                    </div>
                </div>
                <table class="table table-striped jambo_table  car_lead_allocation_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>User Id</th>
                            <th>Name</th>
                            <th>Tiers</th>
                            <th>Quads</th>
                            <th>Total Assigned Leads</th>
                            <th>Last Allocation</th>
                            <th>Max Cap Limit <i class="fa fa-info-circle" id="tooltip" data-toggle="tooltip"
                                    data-placement="top" title="For Unlimited Capactiy Add ( -1 )"></i>
                            </th>
                            <th>Status</th>
                            <th>Last Login</th>
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
