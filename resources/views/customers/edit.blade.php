@extends('layouts.app')
@section('title','Edit Customer')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-edit">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Customer</h2>
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
                <form id="demo-form2" method='post' action="{{ route('customer.update', ['customer' => $customer->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name">First Name <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="first_name" name="first_name" value="{{ $customer->first_name }}"  class="form-control ">
                            @if ($errors->has('first_name'))
                                <span class="text-danger">{{ $errors->first('first_name') }}</span>
                            @endif
                        </div>
                       
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name">Last Name<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="last_name" name="last_name" value="{{ $customer->last_name }}"  class="form-control">
                            @if ($errors->has('last_name'))
                                <span class="text-danger">{{ $errors->first('last_name') }}</span>
                            @endif
                        </div>
                        
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email">Email<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="email" readonly name="email" value="{{ $customer->email }}"  class="form-control">
                            @if ($errors->has('email'))
                                <span class="text-danger">{{ $errors->first('email') }}</span>
                            @endif
                        </div>
                        
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="mobile_no">Mobile No<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="mobile_no" name="mobile_no" value="{{ $customer->mobile_no }}"  class="form-control">
                            @if ($errors->has('mobile_no'))
                                <span class="text-danger">{{ $errors->first('mobile_no') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="gender">Gender<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="gender" name="gender" value="{{ $customer->gender }}"  class="form-control">
                            @if ($errors->has('gender'))
                                <span class="text-danger">{{ $errors->first('gender') }}</span>
                            @endif
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="lang">Lang<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="lang" name="lang" value="{{ $customer->lang }}"  class="form-control">
                            @if ($errors->has('lang'))
                                <span class="text-danger">{{ $errors->first('lang') }}</span>
                            @endif
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">dob<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="datepicker" name="dob" value="{{ $customer->dob }}"  class="form-control">
                            @if ($errors->has('dob'))
                                <span class="text-danger">{{ $errors->first('dob') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="nationality_id">Nationality<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" name="nationality_id">
                                <option value="">Select</option>
                                @foreach ($nationalities as $nationality )
                                    <option value="{{ $nationality->id }}" {{ $customer->nationality_id == $nationality->id ? 'selected':'' }}>{{ $nationality->code }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('nationality_id'))
                                <span class="text-danger">{{ $errors->first('nationality_id') }}</span>
                            @endif
                        </div>
                    </div>


                    <div class="item form-group">
                        <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">Has Alfred Access</label>
                        <div class="col-md-6 col-sm-6 ">
                            <input {{ $customer->has_alfred_access ? 'checked' : '' }} type="checkbox" class="flat" name='has_alfred_access'>
                        </div>
                        <br />
                        @if ($errors->has('has_alfred_access'))
                            <span class="text-danger">{{ $errors->first('has_alfred_access') }}</span>
                        @endif
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                          <button type="submit" class="btn btn-warning">Update & Continue Updating</button> <button type="submit" class="btn btn-warning" id="return_to_view" >Update</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection