@extends('layouts.app')
@section('title','Customer Detail ')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Customer</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('customer.index') }}" class="btn btn-warning btn-sm">Customer List</a></li>
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
                <form id="demo-form2" method='post' action="" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name">
                            <b>First Name : </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                           <p class="label-align-center">{{  $customer->first_name  }}</p>
                        </div>

                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name">
                            <b>Last Name :</b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $customer->last_name  }}</p>
                        </div>

                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email">
                            <b> Email : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $customer->email  }}</p>
                        </div>

                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="mobile_no">
                            <b> Mobile No : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $customer->mobile_no  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="gender">
                            <b>Gender : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $customer->gender  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="lang">
                            <b>Lang: </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $customer->lang  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Dob: </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $customer->dob  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="nationality_id">
                            <b>Nationality: </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $customer->nationality ? $customer->nationality->code : ''  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align"><b>Has Alfred Access : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center"> {{ $customer->has_alfred_access ? 'True' : 'False' }} </p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                            <a href="{{ route('customer.edit', ['customer' => $customer->id])}}"  class='btn btn-warning'>Edit</a>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<div id="auditable">
    <button id='auditablebtn' class="btn btn-warning auditablebtn" data-id="{{ $customer->id }}" data-model="App\Models\Customer">
        View Audit Logs
    </button>
</div>
@endsection
