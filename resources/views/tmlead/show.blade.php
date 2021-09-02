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
                <form id="demo-form2" method='post' action="{{ route('tmleads.update', ['tmlead' => $tmlead->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="CDB ID"><b>CDB ID</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->cdb_id }}</p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Lead Type"><b>Lead Type</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->tmleadtype ? $tmlead->tmleadtype->text : '' }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Lead Status"><b>Lead Status</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->tmleadstatus ? $tmlead->tmleadstatus->text : '' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Call Status"><b>Call Status</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->tmcallstatus ? $tmlead->tmcallstatus->text : '' }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Next Follow-up Date"><b>Next Follow-up Date</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->next_followup_date }}</p>
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
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Phone number"><b>Phone number</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center"><a href="tel://{{ $tmlead->phone_number }}">{{ $tmlead->phone_number }}</a></p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Email Address"><b>Email Address</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->email_address }}</p>
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
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Notes"><b>Notes</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->notes }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Assigned To"><b>Assigned To</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $tmlead->assignedto ? $tmlead->assignedto->name : '' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Uploaded from File"><b>Uploaded from File</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center"><a href="/telemarketing/tmuploadlead/{{ $tmlead->tmuploadleads ? $tmlead->tmuploadleads->id : '' }}">{{ $tmlead->tmuploadleads ? $tmlead->tmuploadleads->file_name : '' }}</a></p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    @if ($tmlead->tminsurancetype->code)
                    @if ($tmlead->tminsurancetype->code == tmInsuranceTypeCode::Car)
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
                            @can('tm-insurance-type-edit')
                            <a id="texta" href="{{ route('tmleads.edit', ['tmlead' => $tmlead->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                            @endcan
                            @can('tm-insurance-type-delete')
                            <a href="#" date-route="{{ route('tmleads.destroy', ['tmlead' => $tmlead->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
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
