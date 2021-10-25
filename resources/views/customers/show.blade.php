@extends('layouts.app')
@section('title','Customer Detail ')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Customer</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('customer.index') }}" class="btn btn-warning btn-sm">Customers List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name"><b>First Name</b></label>
                        <div class="col-md-6 col-sm-6">
                           <p class="label-align-center">{{ $customer->first_name }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name"><b>Last Name</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer->last_name }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email"><b>Email</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer->email }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="mobile_no"><b>Mobile No</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer->mobile_no }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="gender"><b>Gender</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer->gender }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="lang"><b>Language</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer->lang }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob"><b>DOB</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer->dob }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="nationality_id"><b>Nationality</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer->nationality ? $customer->nationality->code : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="has_alfred_access"><b>Has Alfred Access</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer->has_alfred_access ? 'True' : 'False' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="has_reward_access"><b>Has Reward Access</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer->has_reward_access ? 'True' : 'False' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="has_welcome_email_sent"><b>Has Welcome Email Sent</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer->is_we_sent ? 'True' : 'False' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at"><b>Created At</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer->created_at }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b>Updated At</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer->updated_at }}</p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            @can('customers-edit')
                            <a href="{{ route('customer.edit', ['customer' => $customer->id])}}" class='btn btn-warning btn-sm'>Edit</a>
                            @endcan
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="auditable">
    <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $customer->id }}" data-model="App\Models\Customer">View Audit Logs</button>
</div>
@endsection
