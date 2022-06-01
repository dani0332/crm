@extends('layouts.app')
@section('title', 'Lead Allocation Management')
@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <style>
        .la-even {
            background-color: #ffffff !important;
            color: black;
        }

        .la-odd {
            background-color: #E8E8FF !important;
            color: black;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
        }

        input:checked+.slider {
            background-color: #447e03;
        }

        input:focus+.slider {
            box-shadow: 0 0 1px #447e03;
        }

        input:checked+.slider:before {
            -webkit-transform: translateX(26px);
            -ms-transform: translateX(26px);
            transform: translateX(26px);
        }

        /* Rounded sliders */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
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
                stripeClasses: ['la-even', 'la-odd'],
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
                                return `Available <label class="switch"><input type="checkbox" checked><span class="slider round"></span></label>`;
                            } else {
                                return `Unavailable <label class="switch"><input type="checkbox"><span class="slider round"></span></label>`;
                            }
                        },
                    },
                ]
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
                    <table class="table table-striped jambo_table lead_allocation_table" style="width:100%">
                        <thead>
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
