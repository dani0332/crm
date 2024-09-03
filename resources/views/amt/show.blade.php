@extends('layouts.app')
@section('title','GM Lead Detail')
@section('content')

<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Group Medical Lead Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    @if(count($allowedDuplicateLOB) > 0)
                    <li> <a id="duplicateLeadModalBtn" class="btn btn-warning btn-sm">Duplicate Lead</a> </li>
                    @endif
                    <li><a href="{{ url('medical/amt') }}" class="btn btn-warning btn-sm">Group Medical List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <form method="post" action="/quotes/business/manualLeadAssign" class="form-horizontal form-label-left" autocomplete="off">
                    {{ csrf_field() }}
                    @method('POST')
                    <input type="hidden" value="business" name="modelType">
                    <input type="hidden" value="{{ $record->id}}" name="entityId">
                    <div class="col-md-6">
                        <div class="col-md-4">
                            <h2><b>Assign Lead</b></h2>
                        </div>
                        <div class="col-md-4">
                            <select class="form-control" id="assigned_to_id_new"
                            name="assigned_to_id_new">
                                <option>Select Assignee</option>
                                @foreach ($advisors as $item)
                                    <option @if($record->advisor_id == $item->id) selected="selected" @endif value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                            <label id='userAssignValidation' style="display: none;color:red;">Please user for assignment</label>
                        </div>
                        <div class="col-md-4">
                            <button id="assignAfterTeam"
                            name="assignAfterTeam"
                            class="btn btn-warning btn-sm">Assign</button>
                        </div>
                    </div>
                    <div class="clearfix">
                    </div>
                </form>
                <form id="demo-form2" method='post' action="{{ url('medical/amt') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    {{csrf_field()}}
                    @method('PUT')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="id"><b> ID</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->id }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="code"><b> Ref-ID</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->code }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name"><b>FIRST NAME</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->first_name }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name"><b>LAST NAME</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->last_name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="email"><b>EMAIL ADDRESS</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->email }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="mobile_no"><b>MOBILE NUMBER</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->mobile_no }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at"><b>CREATED AT</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->created_at }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b>UPDATED AT</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->updated_at }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="company_name"><b>COMPANY NAME</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->company_name }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="number_of_employees"><b>NUMBER OF EMPLOYEES</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->number_of_employees }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="business_insurance_type"><b>BUSINESS INSURANCE TYPE</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">Group Medical</p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="premium"><b>PREMIUM</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{$record->premium}}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="brief_details"><b>BRIEF DETAILS</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->brief_details }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="assignedUserName"><b>ASSIGNED TO</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{$assignedUserName}}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="assignedGMType"><b>GROUP MEDICAL TYPE</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{$assignedGMType}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="renewal_batch"><b>RENEWAL BATCH</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{$record->renewal_batch}}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="policy_expiry_date"><b>POLICY EXPIRY DATE</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{$record->policy_expiry_date}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="previous_quote_policy_number"><b>PREVIOUS QUOTE POLICY NUMBER</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{$record->previous_quote_policy_number}}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="previous_policy_expiry_date"><b>PREVIOUS QUOTE EXPIRY DATE</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{$record->previous_policy_expiry_date}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="previous_quote_policy_premium"><b>PREVIOUS QUOTE PREMIUM</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{$record->previous_quote_policy_premium}}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="renewal_import_code"><b>RENEWAL IMPORT CODE</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{$record->renewal_import_code}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="parent_duplicate_quote_id"><b>PARENT Ref-ID</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{$record->parent_duplicate_quote_id}}</p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            @can('gm-quotes-edit')
                            <a id="texta" href="{{ url('medical/amt/'. $record->uuid. '/edit') }}" class='btn btn-warning btn-sm'>Edit</a>
                            @endcan
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<x-payments-table :payments="$payments" :paymentMethods="$paymentMethods" :paymentPlainModel="$record" :insuranceProviders="$insuranceProviders" :modeltype="$model->modelType" />

<x-lead-status-update
    :lead="$record"
    :modeltype="$quoteType"
    :status="$record->quote_status_id"
    :statuses="$leadStatuses"
    :lostreasons="$lostReasons"
    :selectedlostreason="$selectedLostReasonId"
    :quoteTypeId="$quoteTypeId"
    :tiers="$tiers" />

    <div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Lead History</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div id="lead-history-div">
                    <table id="leadhistorydatatable" class="table table-striped jambo_table" style="width:100%">
                        <thead>
                            <tr>
                                <th>Modified At</th>
                                <th>Modified By</th>
                                <th>Lead Status</th>
                                <th>Advisor</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" style="text-align: center"> <button id="loadHistoryDataBtn" class="btn btn-success btn-sm">Load History Data</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<x-customer-additional-contact
    :record="$record"
    :quoteType="$quoteType"
    :customerAdditionalContacts="$customerAdditionalContacts" />
<x-customer-additional-contact-modal
    :record="$record"
    :quoteType="$quoteType" />



@if(count($allowedDuplicateLOB) > 0)
    <div class="modal fade" id="duplicateLeadModal" name="duplicateLeadModal" tabindex="-1" role="dialog"
        aria-labelledby="duplicateLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content" style="display: grid;height: 260px !important;">
                    <form method="post" action="/quotes/createDuplicate" autocomplete="off">
                        {{ csrf_field() }}
                        @method('POST')
                        <input type="hidden" value="{{ strtolower($quoteType) }}" name="modelType">
                        <input type="hidden" value="{{ strtolower($quoteType) }}" name="parentType">
                        <input type="hidden" value="{{ strtolower($record->id) }}" name="entityId">
                        <input type="hidden" value="{{ strtolower($record->code) }}" name="entityCode">
                        <input type="hidden" value="{{ strtolower($record->uuid) }}" name="entityUId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="duplicateLeadModalLabel" style="font-size: 16px !important;"><span
                                class="fa fa-clone"></span>
                            <strong style="margin-left: 13px;">Duplicate Lead</strong>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="height: 138px;">
                        <select class="form-control select2" multiple="multiple" id="lob_team" name="lob_team[]">
                            @foreach ($allowedDuplicateLOB as $item)
                                <option value="{{ $item }}">{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer" style="justify-content: center; padding : 0px !important;">
                        <button type="submit" style="margin-top: 13px;" class="btn btn-sm btn-success">Create Duplicate</button>
                    </div>
                </form>
                </div>
        </div>
    </div>
@endif

@endsection
