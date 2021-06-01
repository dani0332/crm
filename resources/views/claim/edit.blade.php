@extends('layouts.app')
@section('title','Edit Claim')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Claim</h2>
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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name">First Name <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="first_name" name="first_name" value="{{ $claim->first_name }}"  class="form-control ">
                            @if ($errors->has('first_name'))
                                <span class="text-danger">{{ $errors->first('first_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name">Last Name <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="last_name" name="last_name" value="{{ $claim->last_name }}"  class="form-control">
                            @if ($errors->has('last_name'))
                                <span class="text-danger">{{ $errors->first('last_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email_address">Email Address <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="email_address" name="email_address" value="{{ $claim->email_address }}"  class="form-control">
                            @if ($errors->has('email_address'))
                                <span class="text-danger">{{ $errors->first('email_address') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="phone_number">Phone Number <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="phone_number" name="phone_number" value="{{ $claim->phone_number }}"  class="form-control">
                            @if ($errors->has('phone_number'))
                                <span class="text-danger">{{ $errors->first('phone_number') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="insurance_company">Insurance Company <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="insurance_company" name="insurance_company" value="{{ $claim->insurance_company }}"  class="form-control">
                            @if ($errors->has('insurance_company'))
                                <span class="text-danger">{{ $errors->first('insurance_company') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="insurance_type">Insurance Type <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id="insurance_type" name="insurance_type">
                                <option value="">Select Insurance Type</option>
                                <option value="Car" {{ $claim->insurance_type == 'Car' ? 'selected' : '' }}>Car</option>
                                <option value="Medical" {{ $claim->insurance_type == 'Medical' ? 'selected' : '' }}>Medical</option>
                                <option value="Travel" {{ $claim->insurance_type == 'Travel' ? 'selected' : '' }}>Travel</option>
                                <option value="Home" {{ $claim->insurance_type == 'Home' ? 'selected' : '' }}>Home</option>
                                <option value="Motorbike" {{ $claim->insurance_type == 'Motorbike' ? 'selected' : '' }}>Motorbike</option>
                                <option value="Life" {{ $claim->insurance_type == 'Life' ? 'selected' : '' }}>Life</option>
                                <option value="Yacht" {{ $claim->insurance_type == 'Yacht' ? 'selected' : '' }}>Yacht</option>
                                <option value="Business" {{ $claim->insurance_type == 'Business' ? 'selected' : '' }}>Business</option>
                            </select>
                            @if ($errors->has('insurance_type'))
                                <span class="text-danger">{{ $errors->first('insurance_type') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="policy_number">Policy Number <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="policy_number" name="policy_number" value="{{ $claim->policy_number }}"  class="form-control">
                            @if ($errors->has('policy_number'))
                                <span class="text-danger">{{ $errors->first('policy_number') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="basic_details">Basic Details <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <textarea id="basic_details" name="basic_details" rows="4" cols="50" class="form-control">{{ $claim->basic_details }}</textarea>
                            @if ($errors->has('basic_details'))
                                <span class="text-danger">{{ $errors->first('basic_details') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                          <button type="submit" class="btn btn-warning">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection