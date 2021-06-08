@extends('layouts.app')
@section('title','Health Quote Detail ')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Health Quote</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('healthquotes.index') }}" class="btn btn-warning btn-sm">Health Quotes List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
             <form id="demo-form2" method='post' action="" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name">
                            <b> Preference</b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                           <p class="label-align-center">{{  $healthquote->preference  }}</p>
                        </div>

                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name">
                            <b>Details </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $healthquote->details  }}</p>
                        </div>

                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email">
                            <b> Has Dental</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{   $healthquote->has_dental  }}</p>
                        </div>

                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="mobile_no">
                            <b>Has Worldwide Cover: </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $healthquote->has_worldwide_cover  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="gender">
                            <b> Has Home</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $healthquote->has_home  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="lang">
                            <b>Marital Status</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $healthquote->marital_status_id   }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Cover For</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $healthquote->cover_for_id  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Emirate Of Registration</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->emirate ? $healthquote->emirate->code :''  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>First Name</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->first_name  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Last Name</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthquote->last_name  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Email</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthquote->email  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Mobile No</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthquote->mobile_no  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Gender</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthquote->gender  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Lang</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthquote->lang  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Source</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthquote->source  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>DOB</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthquote->dob  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Customer</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->customer ? $healthquote->customer->first_name .' '.$healthquote->customer->last_name : ''  }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="nationality_id">
                            <b>Nationality</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->nationality ? $healthquote->nationality->code : ''  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Payment Status</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->paymentStatus ? $healthquote->paymentStatus->code : ''  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Quote Status Id</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->quoteStatus ? $healthquote->quoteStatus->code : ''  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Is Synced</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->is_synced  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Device</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->device  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Reference Url</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->reference_url  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Additional Notes</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->additional_notes  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Reviver Name</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->reviver_name }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Promo Code</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->promo_code }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Code</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthquote->code }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align"><b>Has Alfred Access</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center"> {{$healthquote->has_alfred_access ? 'True' : 'False' }} </p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align"><b>Created At</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center"> {{ $healthquote->created_at }} </p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align"><b>Updated At</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center"> {{ $healthquote->updated_at }} </p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
