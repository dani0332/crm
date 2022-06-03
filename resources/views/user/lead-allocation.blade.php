@extends('layouts.app')
@section('title', 'Lead Allocation Management')
@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link href="{{ asset('css/bootstrap-toggle.css') }}" rel="stylesheet">
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>

    <script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>

    <style>
        .la-even {
            background-color: #ffffff !important;
            color: black;
        }

        .la-odd {
            background-color: #E8E8FF !important;
            color: black;
        }

    </style>
    <script>
        $(document).ready(function() {

            $('.lead_allocation_table').DataTable({
                ordering: false,
                info: true,
                searching: false,
                stateSave: true,
                serverSide: true,
                paging: true,
                processing: true,
                //stripeClasses: ['la-even', 'la-odd'],
                ajax: config.routes.lead_allocation_index_route,
                columns: [{
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
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            if (data == 1) {
                                return `<input type="checkbox" data-id="${row.id}" data-aid="${row.userId}" class="chk" checked data-toggle="toggle">`;
                            } else {
                                return `<input type="checkbox" data-id="${row.id}" data-aid="${row.userId}" class="chk" data-toggle="toggle">`;
                            }
                        },
                    },
                ]
            });

        });

        $(document).on("change", "input:checkbox.chk", function() {
            var ischecked = $(this).is(':checked');
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
                    <table class="table table-striped  table-bordered lead_allocation_table" style="width:100%">
                        <thead class="thead-dark">
                            <tr>
                                <th style="width: 100px !important">User Id</th>
                                <th style="width: 100px !important">Name</th>
                                <th style="width: 100px !important">Team Type</th>
                                <th style="width: 100px !important">Total Assigned Leads</th>
                                <th style="width: 100px !important">Max Cap Limit</th>
                                <th style="width: 100px !important">Status</th>
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
