@extends('layouts.app')
@section('title', 'Lead Allocation Management')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<link href="{{ asset('css/bootstrap-toggle.css') }}" rel="stylesheet">
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>

<script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>

<script>
    var leadAllocationDataTable = null;
    $(document).ready(function() {
        var indexLastColumn = $(".lead_allocation_table").find('tr')[0].cells.length-1;
        leadAllocationDataTable = $('.lead_allocation_table').DataTable({
                info: true,
                serverSide: true,
                searching: false,
                paging: true,
                processing: true,
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
                ],
                drawCallback: function (settings) {
                    $('.lead_allocation_table tr').each(function(){
                        $(this).find('td:last').attr('style', 'float:left;');
                    });
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
</script>


<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Lead Allocation Management</h2>
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
                        <b><span style="color: black;">Health</span></b>
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
                <table class="table table-striped jambo_table  lead_allocation_table" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width: 15% !important">User Id</th>
                            <th style="width: 25% !important">Name</th>
                            <th style="width: 15% !important">Team Type</th>
                            <th style="width: 15% !important">Total Assigned Leads</th>
                            <th style="width: 15% !important">Max Cap Limit <i class="fa fa-info-circle" id="tooltip"
                                    data-toggle="tooltip" data-placement="top"
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
</div>
@endsection
