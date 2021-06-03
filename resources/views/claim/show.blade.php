@extends('layouts.app')
@section('title','Claim Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Claim Detail</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <table class="table table-striped">
                    <tr>
                        <td>Id</td>
                        <td>{{ $claim->id }}</td>
                    </tr>
                    <tr>
                        <td>First Name</td>
                        <td>{{ $claim->first_name }}</td>
                    </tr>
                    <tr>
                        <td>Last Name</td>
                        <td>{{ $claim->last_name }}</td>
                    </tr>
                    <tr>
                        <td>Email Address</td>
                        <td>{{ $claim->email_address }}</td>
                    </tr>
                    <tr>
                        <td>Phone Number</td>
                        <td>{{ $claim->phone_number }}</td>
                    </tr>
                    <tr>
                        <td>Insurance Company</td>
                        <td>{{ $claim->insurance_company }}</td>
                    </tr>
                    <tr>
                        <td>Policy Number</td>
                        <td>{{ $claim->policy_number }}</td>
                    </tr>
                    <tr>
                        <td>Additional Notes</td>
                        <td>{{ $claim->additional_notes }}</td>
                    </tr>
                    <tr>
                        <td>Type of Insurance</td>
                        <td>{{ $claim->typeofinsurance->text }}</td>
                    </tr>
                    <tr>
                        <td>Sub Type of Insurance</td>
                        <td>{{ $claim->subtypeofinsurance_id }}</td>
                    </tr>
                    <tr>
                        <td>Car Make</td>
                        <td>{{ $claim->carmake->text }}</td>
                    </tr>
                    <tr>
                        <td>Car Model</td>
                        <td>{{ $claim->carmodel->text }}</td>
                    </tr>
                    <tr>
                        <td>Claim Status</td>
                        <td>{{ $claim->claimsstatus->text }}</td>
                    </tr>
                    <tr>
                        <td>Car Repair Coverage</td>
                        <td>{{ $claim->carrepaircoverage->text }}</td>
                    </tr>
                    <tr>
                        <td>Car Repair Type</td>
                        <td>{{ $claim->carrepairtype->text }}</td>
                    </tr>
                    <tr>
                        <td>Rent a car</td>
                        <td>{{ $claim->rentacar->text }}</td>
                    </tr>
                    <tr>
                        <td>Assigned To</td>
                        <td>{{ $claim->assignedto->name }}</td>
                    </tr>
                    <tr>
                        <td>Ticket Number</td>
                        <td>{{ $claim->ticket_number }}</td>
                    </tr>
                    <tr>
                        <td>Plate Number</td>
                        <td>{{ $claim->plate_number }}</td>
                    </tr>
                    <tr>
                        <td>Standard Excess payable</td>
                        <td>{{ $claim->standard_excess_payable }}</td>
                    </tr>
                    <tr>
                        <td>Liability</td>
                        <td>{{ $claim->liability }}</td>
                    </tr>
                    <tr>
                        <td>Workshop</td>
                        <td>{{ $claim->workshop }}</td>
                    </tr>
                    <tr>
                        <td>Insurer Reference</td>
                        <td>{{ $claim->insurer_reference }}</td>
                    </tr>
                    <tr>
                        <td>Date of loss</td>
                        <td>{{ $claim->date_of_loss }}</td>
                    </tr>
                    <tr>
                        <td>Claim Amount</td>
                        <td>{{ $claim->claim_amount }}</td>
                    </tr>
                    <tr>
                        <td>Created At</td>
                        <td>{{ $claim->created_at }}</td>
                    </tr>
                    <tr>
                        <td>Updated At</td>
                        <td>{{ $claim->updated_at }}</td>
                    </tr>
                    <tr>
                        @can('claim-edit')
                        <td >
                            <a href="{{ route('claims.edit', ['claim' => $claim->id]) }}" class='no-style-btn'><i class="fa fa-edit"></i> </a>
                        </td>
                        @endcan
                        @can('claim-delete')
                        <td >
                            <form action="{{ route('claims.destroy', ['claim' => $claim->id]) }}" method="POST">  
                                @csrf 
                                @method('DELETE')
                                <button type="submit" class="no-style-btn"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                        @endcan
                    </tr>
                </table >
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection