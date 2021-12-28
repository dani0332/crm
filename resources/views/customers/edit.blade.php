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
                <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('customer.update', ['customer' => $customer->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                @method('PUT')

                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">First Name <span class="required">*</span></span>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $customer->first_name) }}" class="form-control">
                            @if ($errors->has('first_name'))
                                <span class="text-danger">{{ $errors->first('first_name') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Last Name <span class="required">*</span></span>
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $customer->last_name) }}" class="form-control">
                            @if ($errors->has('last_name'))
                                <span class="text-danger">{{ $errors->first('last_name') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Email <span class="required">*</span></span>
                            <input type="text" id="email" name="email" value="{{ old('email', $customer->email) }}" class="form-control">
                            @if ($errors->has('email'))
                                <span class="text-danger">{{ $errors->first('email') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Mobile Number</span>
                            <input type="text" id="mobile_no" name="mobile_no" value="{{ old('mobile_no', $customer->mobile_no) }}" class="form-control">
                        </div>
                    </div>

                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Gender</span>
                            <input type="text" id="gender" name="gender" value="{{ old('gender', $customer->gender) }}" class="form-control">
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Language</span>
                            <input type="text" id="lang" name="lang" value="{{ old('lang', $customer->lang) }}" class="form-control">
                        </div>
                    </div>

                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">DOB</span>
                            <input type="text" id="datepicker" name="dob" value="{{ old('dob', $customer->dob) }}" class="form-control">
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Nationality</span>
                            <select class="form-control" id='nationality_id' name='nationality_id'>
                                <option value=''>Select Nationality</option>
                                @foreach($nationalities as $nationality)
                                    <option value="{{$nationality->id}}"
                                    {{ $nationality->id == old('nationality_id',$customer->nationality_id) ? 'selected' : ''}}>
                                    {{ $nationality->text }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Has Alfred Access</span>
                            <input {{ $customer->has_alfred_access ? 'checked' : '' }} type="checkbox" class="flat" name='has_alfred_access'>
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Has Reward Access</span>
                            <input {{ $customer->has_reward_access ? 'checked' : '' }} type="checkbox" class="flat" name='has_reward_access'>
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
