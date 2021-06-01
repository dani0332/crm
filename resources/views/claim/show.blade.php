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
                        <td>Insurance Type</td>
                        <td>{{ $claim->insurance_type }}</td>
                    </tr>
                    <tr>
                        <td>Policy Number</td>
                        <td>{{ $claim->policy_number }}</td>
                    </tr>
                    <tr>
                        <td>Basic Details</td>
                        <td>{{ $claim->basic_details }}</td>
                    </tr>
                    <tr>
                        <td>Attachment 1</td>
                        <td>{{ $claim->attachment_1 }}</td>
                    </tr>
                    <tr>
                        <td>Attachment 2</td>
                        <td>{{ $claim->attachment_2 }}</td>
                    </tr>
                    <tr>
                        <td>Attachment 3</td>
                        <td>{{ $claim->attachment_3}}</td>
                    </tr>
                    <tr>
                        <td>Attachment 4</td>
                        <td>{{ $claim->attachment_4 }}</td>
                    </tr>
                    <tr>
                        <td>Advisor Name</td>
                        <td>{{ $claim->advisor_name }}</td>
                    </tr>
                    <tr>
                        <td>Ticket Number</td>
                        <td>{{ $claim->ticket_number }}</td>
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