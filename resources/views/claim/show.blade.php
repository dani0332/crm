@extends('layouts.app')
@section('title','Claim Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Claim Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('claims.index') }}" class="btn btn-warning btn-sm">Claims List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('claims.update', ['claim' => $claim->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_namee">
                        <b> First Name </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->first_name }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name">
                        <b> Last Name </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->last_name }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email_address">
                        <b> Email Address </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->email_address }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="phone_number">
                        <b> Phone Number </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->phone_number }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="insurance_company">
                        <b> Insurance Company </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->insurance_company }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="policy_number">
                        <b> Policy Number </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->policy_number }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="additional_notes">
                        <b> Additional Notes </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->additional_notes }}</p>
                        </div>
                    </div>
                    <div class="col">

                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="type_of_insurance">
                        <b> Type of Insurance </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->typeofinsurance ? $claim->typeofinsurance->text : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        @if ($claim->typeofinsurance->text == 'Business')
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="sub_type_of_insurance">
                        <b> Sub Type of Insurance </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->subtypeofinsurance ? $claim->subtypeofinsurance->text : '' }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="claim_status">
                        <b> Claim Status </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->claimsstatus ? $claim->claimsstatus->text : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="assigned_to">
                        <b> Assigned To </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->assignedto ? $claim->assignedto->name : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="insurer_reference">
                        <b> Insurer Reference </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->insurer_reference }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="date_of_loss">
                        <b> Date of loss </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->date_of_loss }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="claim_amount">
                        <b> Claim Amount </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->claim_amount }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="ticket_number">
                        <b> Ticket Number </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->ticket_number }}</p>
                        </div>
                    </div>
                </div>
                @if ($claim->typeofinsurance->text == 'Car')
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_make">
                        <b> Car Make </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->carmake ? $claim->carmake->text : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_model">
                        <b> Car Model </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->carmodel ? $claim->carmodel->text : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_repair_coverage">
                        <b> Car Repair Coverage </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->carrepaircoverage ? $claim->carrepaircoverage->text : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_repair_type">
                        <b> Car Repair Type </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->carrepairtype ? $claim->carrepairtype->text : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="rent_a_car">
                        <b> Rent a car </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->rentacar ? $claim->rentacar->text : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="plate_number">
                        <b> Plate Number </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->plate_number }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="standard_excess_payable">
                        <b> Standard Excess payable </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->standard_excess_payable }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="lability">
                        <b> Liability </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->liability }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="workshop">
                        <b> Workshop </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->workshop }}</p>
                        </div>
                    </div>
                    <div class="col">

                    </div>
                </div>
                @endif
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="attachment_1">
                        <b> Attachment 1 </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            @if($claim->attachment_1 != '')
                            <p class="label-align-center"><a href="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$claim->attachment_1 }}" target="_blank">Open file</a></p>
                            @else
                            <p class="label-align-center">File not uploaded!</p>
                            @endif
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="attachment_2">
                        <b> Attachment 2 </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            @if($claim->attachment_2 != '')
                            <p class="label-align-center"><a href="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$claim->attachment_2 }}" target="_blank">Open file</a></p>
                            @else
                            <p class="label-align-center">File not uploaded!</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="attachment_3">
                        <b> Attachment 3 </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            @if($claim->attachment_3 != '')
                            <p class="label-align-center"><a href="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$claim->attachment_3 }}" target="_blank">Open file</a></p>
                            @else
                            <p class="label-align-center">File not uploaded!</p>
                            @endif
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="attachment_4">
                        <b> Attachment 4 </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            @if($claim->attachment_4 != '')
                            <p class="label-align-center"><a href="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$claim->attachment_4 }}" target="_blank">Open file</a></p>
                            @else
                            <p class="label-align-center">File not uploaded!</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at">
                        <b> Created At </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->created_at }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_by">
                        <b> Created by </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->createdby ? $claim->createdby->name : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at">
                        <b> Updated At </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->updated_at }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="modified_by">
                        <b> Updated by </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->modifiedby ? $claim->modifiedby->name : '' }}</p>
                        </div>
                    </div>
                </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            @can('claim-edit')
                            <a id="texta" href="{{ route('claims.edit', ['claim' => $claim->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                            @endcan
                            @can('claim-delete')
                            <form action="{{ route('claims.destroy', ['claim' => $claim->id]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-warning btn-sm">Delete</button>
                            </form>
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
        <button id='auditablebtn' class="btn btn-warning btn-sm" data-id="{{ $claim->id }}" data-model="App\Models\ClaimController">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection
