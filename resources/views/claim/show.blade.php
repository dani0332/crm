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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="firstname">
                            <b> First Name </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->first_name }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="lastname">
                            <b> Last Name </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->last_name }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Email Address </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->email_address }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Phone Number </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->phone_number }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Insurance Company </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->insurance_company }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Policy Number </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->policy_number }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Additional Notes </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->additional_notes }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Type of Insurance </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->typeofinsurance ? $claim->typeofinsurance->text : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Sub Type of Insurance </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->subtypeofinsurance ? $claim->subtypeofinsurance->text : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Car Make </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->carmake ? $claim->carmake->text : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Car Model </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->carmodel ? $claim->carmodel->text : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Claim Status </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->claimsstatus ? $claim->claimsstatus->text : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Car Repair Coverage </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->carrepaircoverage ? $claim->carrepaircoverage->text : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Car Repair Type </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->carrepairtype ? $claim->carrepairtype->text : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Rent a car </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->rentacar ? $claim->rentacar->text : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Assigned To </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->assignedto ? $claim->assignedto->name : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Ticket Number </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->ticket_number }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Plate Number </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->plate_number }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Standard Excess payable </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->standard_excess_payable }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Liability </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->liability }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Workshop </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->workshop }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Insurer Reference </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->insurer_reference }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Date of loss </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->date_of_loss }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Claim Amount </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->claim_amount }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Created At </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->created_at }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="SSSSS">
                            <b> Updated At </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claim->updated_at }}</p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
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
                            </tr>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning auditablebtn" data-id="{{ $claim->id }}" data-model="App\Models\ClaimController">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection