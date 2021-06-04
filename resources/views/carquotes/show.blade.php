@extends('layouts.app')
@section('title','Car Quote Detail ')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Car Quote</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('carquotes.index') }}" class="btn btn-warning btn-sm">Car Quotes List</a></li>
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
                            <b> Car Value : </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                           <p class="label-align-center">{{  $carquote->car_value  }}</p>
                        </div>

                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name">
                            <b>Currently Insured With :</b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carquote->currently_insured_with  }}</p>
                        </div>

                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email">
                            <b> Year Of Manufacture : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carquote->year_of_manufacture  }}</p>
                        </div>

                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="mobile_no">
                            <b>UAE License Held For : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->uaeLicenseHeldFor ? $carquote->uaeLicenseHeldFor->code : ''  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="gender">
                            <b> Car Make : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->carMake ? $carquote->carMake->code :''  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="lang">
                            <b>Car Model : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carquote->carModel ? $carquote->carModel->code : ''   }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Car Value : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carquote->car_value  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Emirate Of Registration : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->emirate ? $carquote->emirate->code : ''  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>First Name : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carquote->first_name  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Last Name : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->last_name  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Email : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->email  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Claim History : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->claimHistory ? $carquote->claimHistory->code :''  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Car Type Insurance : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->carTypeInsurance ? $carquote->carTypeInsurance->code : ''  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Mobile No : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->mobile_no  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Gender : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->gender  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Lang : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->lang  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Source : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->source  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>DOB : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->dob  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Customer : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->customer ? $carquote->customer->first_name .' '. $carquote->customer->last_name : ''  }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="nationality_id">
                            <b>Nationality: </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carquote->nationality ? $carquote->nationality->code : '' }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Payment Status : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carquote->paymentStatus ? $carquote->paymentStatus->code : ''  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Quote Status Id : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->quoteStatus ? $carquote->quoteStatus->code : '' }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Is Synced : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->is_synced  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Device : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->device  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Reference Url : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->reference_url  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Additional Notes : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->additional_notes  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Reviver Name : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->reviver_name }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Promo Code : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carquote->promo_code }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Code : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carquote->code }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align"><b>Has Alfred Access : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center"> {{ $carquote->has_alfred_access ? 'True' : 'False' }} </p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
