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
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('claims.update', ['claim' => $claim->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name"><b> First Name </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->first_name }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name"><b> Last Name </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->last_name }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email_address"><b> Email Address </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->email_address }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="phone_number"><b> Phone Number </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->phone_number }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="insurance_provider_id"><b> Insurance Company </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->insuranceprovider ? $claim->insuranceprovider->text : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="policy_number"><b> Policy Number </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->policy_number }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="additional_notes"><b> Additional Notes </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->additional_notes }}</p>
                        </div>
                    </div>
                    <div class="col">

                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="type_of_insurance"><b> Type of Insurance </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->typeofinsurance ? $claim->typeofinsurance->text : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        @if ($claim->typeofinsurance)
                        @if ($claim->typeofinsurance->text == 'Business')
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="sub_type_of_insurance"><b> Sub Type of Insurance </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->subtypeofinsurance ? $claim->subtypeofinsurance->text : '' }}</p>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="claim_status"><b> Claim Status </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->claimsstatus ? $claim->claimsstatus->text : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="assigned_to"><b> Assigned To </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->assignedto ? $claim->assignedto->name : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="insurer_reference"><b> Insurer Reference </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->insurer_reference }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="date_of_loss"><b> Date of loss </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->date_of_loss }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="claim_amount"><b> Claim Amount </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->claim_amount }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="ticket_number"><b> Ticket Number </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->ticket_number }}</p>
                        </div>
                    </div>
                </div>
                @if ($claim->typeofinsurance)
                @if ($claim->typeofinsurance->text == 'Car')
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_make"><b> Car Make </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->carmake ? $claim->carmake->text : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_model"><b> Car Model </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->carmodel ? $claim->carmodel->text : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_repair_coverage"><b> Car Repair Coverage </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->carrepaircoverage ? $claim->carrepaircoverage->text : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_repair_type"><b> Car Repair Type </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->carrepairtype ? $claim->carrepairtype->text : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_rent_a_car"><b> Rent a car </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center"> {{ $claim->is_rent_a_car ? 'Yes' : 'No' }} </p>
                        {{--<p class="label-align-center">{{ $claim->rentacar ? $claim->rentacar->text : '' }}</p>--}}
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="plate_number"><b> Plate Number </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->plate_number }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="standard_excess_payable"><b> Standard Excess payable </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->standard_excess_payable }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="lability"><b> Liability </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->liability }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="workshop"><b> Workshop </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->workshop }}</p>
                        </div>
                    </div>
                    <div class="col">

                    </div>
                </div>
                @endif
                @endif
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at"><b> Created At </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->created_at }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_by"><b> Created by </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->createdby ? $claim->createdby->name : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b> Updated At </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $claim->updated_at }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="modified_by"><b> Updated by </b></label>
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
                            @if ($claim->claimsstatus->text != 'Settled')
                            <a id="texta" href="{{ route('claims.edit', ['claim' => $claim->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                            @endif
                            @endcan
                            @can('claim-delete')
                            <a href="#" date-route="{{ route('claims.destroy', ['claim' => $claim->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
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
                <h2>Claims Attachments</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('claims.claim-attachment.create',['claim'=>$claim->id]) }}" class="btn btn-warning btn-sm">Create Claim Attachment</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                    <thead>
                      <tr>
                        <th>id</th>
                        <th>File</th>
                        <th>Created At</th>
                        <th>Created by</th>
                      </tr>
                    </thead>

                    <tbody>
                      @foreach($claim->claimsAttachments as $key => $claimAttachment)
                      <tr>
                        <td><a href="{{ route('claims.claim-attachment.show', ['claim'=>$claim->id,'claim_attachment' => $claimAttachment->id]) }}">
                        {{ $claimAttachment->id }}</a></td>
                        <td><a href="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$claimAttachment->file_name }}" target="_blank">{{$claimAttachment->file_original_name}}</a></td>
                        <td>{{ $claimAttachment->created_at }}</td>
                        <td>{{ $claimAttachment->createdby ? $claimAttachment->createdby->name : '' }}</td>
                      </tr>
                      @endforeach
                    </tbody>
                  </table>
            </div>
        </div>
    </div>
</div>

@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $claim->id }}" data-model="App\Models\Claim">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection
