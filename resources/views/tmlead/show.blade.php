@extends('layouts.app')
@section('title','TM Lead')
@section('content')
<?php
use App\Enums\tmInsuranceTypeCode;
?>
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>TM Lead Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('tmleads.index') }}" class="btn btn-warning btn-sm">TM Lead List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="ID"><b>ID</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->cdb_id }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Lead Type"><b>Lead Type</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->tmleadtype ? $tmlead->tmleadtype->text : '' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Customer Name"><b>Customer Name</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->customer_name }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Insurance Type"><b>Insurance Type</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->tminsurancetype ? $tmlead->tminsurancetype->text : '' }}</p>
                            </div>
                        </div>
                        
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Email Address"><b>Email Address</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->email_address }}
                            @if(count($tmlead->additionalInformation) > 0)
                                @foreach($tmlead->additionalInformation as $info)
                                , {{ $info->email_address }}
                                @endforeach
                            @endif
                            </p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Phone number"><b>Phone number</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">
                                <a href="tel:{{ $customerCorrectPhoneNo }}" id="ignore-redirection" style="text-decoration:underline;">{{ $customerCorrectPhoneNo }}</a>
                                @if(count($tmlead->additionalInformation) > 0)
                                    @foreach($tmlead->additionalInformation as $info)
                                    , <a href="tel:{{ mapPhoneNumber($info->phone_number) }}" id="ignore-redirection" style="text-decoration:underline;">{{ mapPhoneNumber($info->phone_number) }}</a>
                                    @endforeach
                                @endif
                            </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Enquiry date"><b>Enquiry date</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->enquiry_date }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Allocation Date"><b>Allocation Date</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->allocation_date }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Assigned To"><b>Assigned To</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->assignedto ? $tmlead->assignedto->name : '' }}</p>
                            </div>
                        </div>
                        <div class="col">
                            @if ($tmInsuranceTypeCode)
                            @if ($tmInsuranceTypeCode == tmInsuranceTypeCode::Car || $tmInsuranceTypeCode == tmInsuranceTypeCode::Bike
                            || $tmInsuranceTypeCode == tmInsuranceTypeCode::Life || $tmInsuranceTypeCode == tmInsuranceTypeCode::Health)
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="DOB"><b>DOB</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->dob }}</p>
                            </div>
                            @endif
                            @endif
                        </div>
                    </div>
                    @if ($tmInsuranceTypeCode)
                    @if ($tmInsuranceTypeCode == tmInsuranceTypeCode::Car)
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Nationality"><b>Nationality</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->nationality ? $tmlead->nationality->text : '' }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Years of driving"><b>Years of driving</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->yearsofdriving ? $tmlead->yearsofdriving->text : '' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Car Make"><b>Car Make</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->carmake ? $tmlead->carmake->text : '' }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Car Model"><b>Car Model</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->carmodel ? $tmlead->carmodel->text : '' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Year of Manufacture"><b>Year of Manufacture</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->year_of_manufacture }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Emirates of Registration"><b>Emirates of Registration</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->emiratesofregistration ? $tmlead->emiratesofregistration->text : '' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Car Value"><b>Car Value</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->car_value }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Car Type of Insurance"><b>Car Type of Insurance</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->cartypeofinsurance ? $tmlead->cartypeofinsurance->text : '' }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endif
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Created At"><b>Created At</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->created_at }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Created By"><b>Created By</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->createdby ? $tmlead->createdby->name : '' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Updated At"><b>Updated At</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->updated_at }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Updated By"><b>Updated By</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->updatedby ? $tmlead->updatedby->name : '' }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            @can('telemarketing-edit')
                                @if($isLeadEditable == "1")
                                    <a id="texta" href="{{ route('tmleads.edit', ['tmlead' => $tmlead->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                                @endif
                            @endcan
                            @can('telemarketing-delete')
                            <a href="#" date-route="{{ route('tmleads.destroy', ['tmlead' => $tmlead->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
                            @endcan
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Update TM Lead Status & Notes</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="post" action="{{ $tmlead->id }}/tmLeadUpdate" class="form-horizontal form-label-left" role="form" data-parsley-validate=""novalidate="" autocomplete="off">
                {{csrf_field()}}
                @method('GET')
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6"><b>Lead Status</b> <span class="required">*</span></span>
                            <select class="form-control" id="tm_lead_statuses_id" name="tm_lead_statuses_id" data-toggle="tooltip" data-placement="top" title="Please select lead status">
                            <option value=""></option>
                            @foreach($tmLeadStatuses as $tmLeadStatus)
                                <option value="{{$tmLeadStatus->id}}" data-id="{{ $tmLeadStatus->code }}"
                                {{ $tmLeadStatus->id == old('tm_lead_statuses_id',$tmlead->tm_lead_statuses_id) ? 'selected' : ''}}>
		                        {{ $tmLeadStatus->text }}
                                </option>
                            @endforeach
                            </select>
                            @if ($errors->has('tm_lead_statuses_id'))
                                <span class="text-danger">{{ $errors->first('tm_lead_statuses_id') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <div id="next_followup_date_field">
                                <span class="col-form-label col-md-6 col-sm-6"><b>Next Follow-up Date & Time</b> <span class="required">*</span></span>
                                <input type="text" id="next_followup_date" name="next_followup_date" value="{{ old('next_followup_date', $tmlead->next_followup_date) }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please select next follow-up date & time">
                                @if ($errors->has('next_followup_date'))
                                    <span class="text-danger">{{ $errors->first('next_followup_date') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6"><b>Notes</b></span>
                            <textarea id="notes" name="notes" rows="4" cols="50" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter notes">{{ old('notes', $tmlead->notes) }}</textarea>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="No Answer/Switched Off Count"><b>No Answer/Switched Off (Count)</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->no_answer_count ? $tmlead->no_answer_count : '0' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <input type="hidden" id="tmLeadEditFormNextFollowupDate" name="tmLeadEditFormNextFollowupDate" value="{{ $tmlead->next_followup_date }}">
                            <input type="hidden" id="tmLeadId" name="tmLeadId" value="{{ $tmlead->id }}">
                            <input type="hidden" id="no_answer_count" name="no_answer_count" value="{{ $tmlead->no_answer_count }}">
                            @can('telemarketing-edit')
                                <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Update</button>
                            @endcan
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $tmlead->id }}" data-model="App\Models\TmLead">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection
