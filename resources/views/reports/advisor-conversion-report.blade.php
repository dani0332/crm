@extends('layouts.app')
@section('title','Advisor Conversion Report')
@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
<style type="text/css">
    .dropdown-toggle {
        height: 40px;
        width: 400px !important;
    }

    .filter-option {
        border: 1px solid #458BC3 !important;
    }
    .col-form-label{
        font-weight: 600 !important;
        color: black !important;
    }
</style>
<script>
    $(function(){
        var claimsDatatable = $('.claim-data-table').DataTable({
            ordering: false,
            info: false,
            searching: false,
            bLengthChange: false,
            serverSide: true,
            ajax: {
                url: config.routes.claim_datatable_route,
                data: function (d) {
                    d.searchtype = $('#search_type').val();
                    d.searchfield = $('input[name=searchfield]').val();
                    d.claimstatus = $('#claim_status_value').val();
                    d.assignedto = $('#assigned_to_value').val();
                    d.type_of_insurance = $('#type_of_insurance_value').val();
                },
            },
            columns: [
                {
                    data: 'id',
                    name: 'id',
                    render: function (data, type, row) {
                        return (
                            "<a href='" +
                            config.routes.claim_datatable_route +
                            '/' +
                            row.id +
                            "'>" +
                            row.id +
                            '</a>'
                        );
                    },
                },
                { data: 'ticket_number', name: 'ticket_number' },
                { data: 'policy_number', name: 'policy_number' },
                { data: 'first_name', name: 'first_name' },
                { data: 'last_name', name: 'last_name' },
                { data: 'email_address', name: 'email_address' },
                { data: 'phone_number', name: 'phone_number' },
                { data: 'type_of_insurance_text', name: 'type_of_insurance_text' },
                { data: 'claims_status_text', name: 'claims_status_text' },
                { data: 'created_at', name: 'created_at' },
                { data: 'updated_at', name: 'updated_at' },
            ],
        });
    });
</script>
<div class="">
    <div class="row">
        <div class="x_panel transparent">
            <div class="col-md-12">
                <div class="col col-md-3" style="margin-right: 75px !important;">
                    <span class="col-form-label col-md-12">Team</span>
                    <select class="selectpicker" name="teams[]" multiple data-live-search="true">
                        @foreach ($teams as $team ))
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col col-md-3" style="margin-right: 75px !important;">
                    <span class="col-form-label col-md-12">Batch Number</span>
                    <select class="selectpicker" name="batchNumbers[]" multiple data-live-search="true">
                        @foreach ($batches as $batch ))
                        <option value="{{ explode('|', $batch)[0] }}">{{ explode('|', $batch)[1] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col col-md-3" style="margin-right: 75px !important;">
                    <span class="col-form-label col-md-12">Advisor Name</span>
                    <select id="advisor" class="selectpicker" name="advisors[]" multiple data-live-search="true">
                        @foreach ($users as $user ))
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col col-md-3" style="margin-right: 75px !important;">
                    <span class="col-form-label col-md-12">Tier</span>
                    <select class="selectpicker" name="tiers[]" multiple data-live-search="true">
                        @foreach ($tiers as $tier ))
                        <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col col-md-3" style="margin-right: 75px !important;">
                    <span class="col-form-label col-md-12">Exclude Created Leads</span>
                    <select class="selectpicker" name="excludeCreated" data-live-search="true">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col col-md-3" style="margin-right: 75px !important;">
                    <span class="col-form-label col-md-12">Lead Source</span>
                    <select class="selectpicker" name="leadSources[]" multiple data-live-search="true">
                        @foreach ($leadSources as $leadSource ))
                        <option value="{{ $leadSource->id }}">{{ $leadSource->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col col-md-3" style="margin-right: 75px !important;">
                    <span class="col-form-label col-md-12">Ecommerce</span>
                    <select class="selectpicker" name="ecommerce" data-live-search="true">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col col-md-3" style="margin-right: 75px !important;float:right;margin-top: 20px;">
                    <button class="btn btn-primary">Go</button>
                </div>
            </div>
            <div class="col-md-12">
                @if(session()->has('message'))
                <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <table class="table table-striped jambo_table claim-data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Batch Number</th>
                            <th>Start Date</th>
                            <th>Stop Date</th>
                            <th>Advisor Name</th>
                            <th>Total Leads</th>
                            <th>New Leads</th>
                            <th>NI</th>
                            <th>In progress</th>
                            <th>Manual Created</th>
                            <th>Bad Lead</th>
                            <th>Sale</th>
                            <th>Completed</th>
                            <th>AFIA</th>
                            <th>Gross Conversion</th>
                            <th>Net Conversion</th>
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
