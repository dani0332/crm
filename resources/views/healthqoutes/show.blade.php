@extends('layouts.app')
@section('title','Health Qoute Detail ')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Health Qoute</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
             <form id="demo-form2" method='post' action="" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name">
                            <b> Preference : </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                           <p class="label-align-center">{{  $healthqoute->preference  }}</p>
                        </div>
                       
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name">
                            <b>Details :</b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $healthqoute->details  }}</p>
                        </div>
                        
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email">
                            <b> Has Dental : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{   $healthqoute->has_dental  }}</p>
                        </div>
                        
                    </div>
                    

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="mobile_no">
                            <b>Has Worldwide Cover: </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $healthqoute->has_worldwide_cover  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="gender">
                            <b> Has Home : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $healthqoute->has_home  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="lang">
                            <b>Marital Status : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $healthqoute->marital_status_id   }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Cover For : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $healthqoute->cover_for_id  }}</p>
                        </div>
                    </div>
                    
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Emirate Of Registration : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{  $healthqoute->emirate->code  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>First Name : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthqoute->first_name  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Last Name : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->last_name  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Email : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->email  }}</p>
                        </div>
                    </div>                    

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Mobile No : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->mobile_no  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Gender : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->gender  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Lang : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->lang  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Source : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->source  }}</p>
                        </div>
                    </div>
                    

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>DOB : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->dob  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Customer : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->customer->first_name .' '.$healthqoute->customer->last_name  }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="nationality_id">
                            <b>Nationality: </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthqoute->nationality->code  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Payment Status : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->paymentStatus->code  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Quote Status Id : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->quoteStatus->code  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Is Synced : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->is_synced  }}</p>
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Device : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->device  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Reference Url : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->reference_url  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Additional Notes : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->additional_notes  }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Reviver Name : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->reviver_name }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Promo Code : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$healthqoute->promo_code }}</p>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="dob">
                            <b>Code : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $healthqoute->code }}</p>
                        </div>
                    </div>
                    
                    <div class="item form-group">
                        <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align"><b>Has Alfred Access : </b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center"> {{$healthqoute->has_alfred_access ? 'True' : 'False' }} </p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection