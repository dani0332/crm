@extends('layouts.app')
@section('title','Car Qoute Detail ')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Car Qoute</h2>
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
                           <p class="label-align-center">{{  $carqoute->car_value  }}</p>
                        </div>
                       
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name">
                            <b>Currently Insured With :</b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carqoute->currently_insured_with  }}</p>
                        </div>
                        
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email">
                            <b> Year Of Manufacture : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carqoute->year_of_manufacture  }}</p>
                        </div>
                        
                    </div>
                    

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="mobile_no">
                            <b>UAE License Held For : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carqoute->uaeLicenseHeldFor->code  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="gender">
                            <b> Car Make : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carqoute->carMake->code  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="lang">
                            <b>Car Model : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carqoute->carModel->code   }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Car Value : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carqoute->car_value  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Emirate Of Registration : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carqoute->emirate->code  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>First Name : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carqoute->first_name  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Last Name : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->last_name  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Email : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->email  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Claim History : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->claimHistory->code  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Car Type Insurance : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->carTypeInsurance->code  }}</p>
                        </div>
                    </div>
                    

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Mobile No : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->mobile_no  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Gender : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->gender  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Lang : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->lang  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Source : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->source  }}</p>
                        </div>
                    </div>
                    

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>DOB : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->dob  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Customer : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->customer->first_name .' '. $carqoute->customer->last_name  }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="nationality_id">
                            <b>Nationality: </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carqoute->nationality->code  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Payment Status : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->paymentStatus->code  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Quote Status Id : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->quoteStatus->code  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Is Synced : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->is_synced  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Device : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->device  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Reference Url : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->reference_url  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Additional Notes : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->additional_notes  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Reviver Name : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->reviver_name }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Promo Code : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $carqoute->promo_code }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Code : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $carqoute->code }}</p>
                        </div>
                    </div>
                    
                    <div class="item form-group">
                        <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align"><b>Has Alfred Access : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center"> {{ $carqoute->has_alfred_access ? 'True' : 'False' }} </p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection