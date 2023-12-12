@extends('layouts.app')
@section('title', 'Lead Allocation Management')
@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <link href="{{ asset('css/bootstrap-toggle.css') }}" rel="stylesheet">
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>

    <script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>

    <script>
        function getStatusText(statusId) {
            var statusText = '';
            switch (parseInt(statusId)) {
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

        var leadAllocationDataTable = null;
        $(document).ready(function () {
            var isAutoAllocationWorking = JSON.parse('<?php
                                                      echo json_encode($isAutoAllocationWorking); ?>');
            var indexLastColumn = $(".lead_allocation_table").find('tr')[0].cells.length - 1;
            leadAllocationDataTable = $('.lead_allocation_table').DataTable({
                info: true,
                serverSide: true,
                searching: false,
                paging: true,
                ordering: true,
                lengthChange: false,
                ajax: config.routes.lead_allocation_index_route,
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
                        data: 'teamName',
                        name: 'teamName',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'allocation_count',
                        name: 'allocation_count',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'last_allocated',
                        name: 'last_allocated',
                        orderable: true,
                        searchable: false,
                        render: function (data, type, row) {
                            if (data == null) {
                                return '-';
                            } else {
                                return new Date(data * 1000).toLocaleString();
                            }
                        }
                    },
                    {
                        data: 'max_capacity',
                        name: 'max_capacity',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'is_available',
                        name: 'is_available',
                        orderable: true,
                        searchable: false,
                        render: function (data, type, row) {
                            var statusText = getStatusText(data);
                            if (data == 1) {
                                var html = `
                                <span class="status-text">${statusText}</span><label class="switch" style="margin-left: 20px;">
                                            <input data-toggle="toggle"  data-size="lg" type="checkbox" data-id="${row.id}" data-userId="${row.userId}" checked="checked" class="chk success" id="is_active" name="is_active">
                                            <span class="slider round"></span>
                                        </label>`;

                                return html;
                            } else {
                                var html = `<span class="status-text">${statusText}</span><label class="switch " style="margin-left: 20px;">
                                                            <input type="checkbox" data-id="${row.id}" data-userId="${row.userId}" class="chk danger" id="is_active" name="is_active">
                                                            <span class="slider round"></span>
                                                        </label>`;
                                return html;
                            }
                        },
                    },
                ],
                drawCallback: function (settings) {
                    $('.lead_allocation_table tr').each(function(){
                        $(this).find('td:last').attr('style', 'float:left;');
                    });
                    if(isAutoAllocationWorking == '0') {
                        $inputs = $('.chk');
                        $inputs.each(function(){ $(this).attr('disabled', true); });
                    }
                }
            });


            $('body').on('dblclick', 'table:first td:nth-last-child(2)', function() {
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
                        var userId = $(this).next().find('input').attr('data-userId');
                        var id = $(this).next().find('input').attr('data-id');
                        var data = {
                            'max_cap': maxCap,
                            'userId': userId,
                            'id': id,
                            'team_type' : 'health',
                            '_token': $('meta[name="csrf-token"]').attr('content')
                        };
                        $.ajax({
                            url: '/lead-allocation/update-availability',
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

        $(document).on("change", "input:checkbox.leadSwitch", function() {

            if(confirm("Are you sure you want to change Leads Assignment Status ?")){
                var ischecked = $(this).is(':checked');
                $.ajax({
                    url: '/lead-allocation/toggle-lead-allocation-job-status',
                    type: 'POST',
                    data : {
                        '_token': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        //changeAvailabilityInputs(ischecked);
                    }
                });
            }
            else{
                $('.leadSwitch').prop('checked', $('.leadSwitch').val() == 'on' ? true : false);
                return false;
            }
        });
</script>

<div class="row">
    <div class="x_panel">
        <div class="x_title">
            <h2>Lead Allocation Management</h2>
            @if(Auth::user()->isAdmin())
                <span class="status-text"></span><label class="switch " style="margin-left: 20px;float: left;margin-top: 5px;">
                    <input type="checkbox" @if($isAutoAllocationWorking == '1')  checked="checked" @endif class="leadSwitch success" id="jobSwitch" name="jobSwitch">
                    <span class="slider round"></span>
                </label>
            @endif
            <div class="clearfix"></div>
        </div>
        <div class="col-md-3 col-lg-2" style="border-radius: 10px; border-left: 3px solid #A1C86B;margin-bottom: 20px;font-size: 26px;background: whitesmoke;height: 120px;padding-top: 10px;">
            <span style="font-size: 18px">Team </span>
            <br/>
            <b><span style="color: black;">Health</span></b>
        </div>
        <div class="col-md-3 col-lg-2" style="border-radius: 10px; border-left: 3px solid #4183BD; margin-left: 70px;margin-bottom: 20px;font-size: 26px;background: whitesmoke;height: 120px;padding-top: 10px;">
            <span style="font-size: 18px">Assigned Lead Count </span>
            <br/>
            <b><span style="color: black;">{{$totalAssignedLeadCount}}</span></b>
        </div>
        <div class="col-md-3 col-lg-2" style="border-radius: 10px; border-left: 3px solid #3015ca; margin-left: 70px;margin-bottom: 20px;font-size: 26px;background: whitesmoke;height: 120px;padding-top: 10px;">
            <span style="font-size: 18px">Total Advisors</span>
            <br/>
            <b><span style="color: black;">{{ $unAvailableUsers + $availableUsers}}</span></b>
        </div>
        <div class="col-md-3 col-lg-2" style="border-radius: 10px; border-left: 3px solid #facb19; margin-left: 70px;margin-bottom: 20px;font-size: 26px;background: whitesmoke;height: 120px;padding-top: 10px;">
            <span style="font-size: 18px;">Availabe / UnAvailable</span>
            <br/>
            <b><span style="color: black;"><label id="availableUsers">{{$availableUsers}} </label> /
                        <label id="UnavailableUsers">{{$unAvailableUsers}}</label></span></b>
        </div>
        <div class="col-md-12 col-lg-12">
            <span style="font-size: 18px;">Unassigned leads count</span>
        </div>
        <div class="col-md-3 col-lg-2" style="margin-top: 10px; border-radius: 10px; border-left: 3px solid #e46122;margin-bottom: 20px;font-size: 26px;background: whitesmoke;height: 120px;padding-top: 10px;">
            <span style="font-size: 18px">Good </span>
            <br/>
            <b><span style="color: black;">{{ $unAssignedGood }}</span></b>
        </div>
        <div class="col-md-3 col-lg-2" style="margin-top: 10px; border-radius: 10px; border-left: 3px solid #db8b1d; margin-left: 70px;margin-bottom: 20px;font-size: 26px;background: whitesmoke;height: 120px;padding-top: 10px;">
            <span style="font-size: 18px">Best</span>
            <br/>
            <b><span style="color: black;">{{ $unAssignedBest }}</span></b>
        </div>
        <div class="col-md-3 col-lg-2" style="margin-top: 10px; border-radius: 10px; border-left: 3px solid #d80ca8; margin-left: 70px;margin-bottom: 20px;font-size: 26px;background: whitesmoke;height: 120px;padding-top: 10px;">
            <span style="font-size: 18px">Entry Level</span>
            <br/>
            <b><span style="color: black;">{{ $unAssignedEntryLevel }}</span></b>
        </div>
        <div class="col-md-12 col-sm-12 ">
            <div class="x_content">
                <br/>
                @if (session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                @if (session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif

                <table class="table table-striped jambo_table  lead_allocation_table" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width: 15% !important">User Id</th>
                            <th style="width: 25% !important">Name</th>
                            <th style="width: 15% !important">Team Type</th>
                            <th style="width: 15% !important">Total Assigned Leads</th>
                            <th style="width: 15% !important">Last Allocation</th>
                            <th style="width: 15% !important">
                                Max Cap Limit
                                <i class="fa fa-info-circle" id="tooltip" data-toggle="tooltip" data-placement="top"
                                   title="For Unlimited Capactiy Add ( -1 )"></i>
                            </th>
                            <th style="width: 12% !important;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
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
