@extends('layouts.app')
@section('title', $quote_type_text)
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>{{ $quote_type_text }} Quote</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('aml.index') }}" class="btn btn-warning btn-sm">Home</a></li>
                    @if($quote_request->quote_status_id == 1 || $quote_request->quote_status_id == 3)
                    <li><a href="#" class='btn btn-warning btn-sm' style="opacity: .4;cursor: default !important;pointer-events: none;">Reject</a></li>
                    <li><a href="#" class="btn btn-warning btn-sm" style="opacity: .4;cursor: default !important;pointer-events: none;">Approve</a></li>
                    @else
                    <li><a href="{{ $quote_request->id }}/quote_status_rejected" class='btn btn-warning btn-sm' onclick="return confirm('Do you really want to reject this item?');">Reject</a></li>
                    <li><a href="{{ $quote_request->id }}/quote_status_approved" class="btn btn-warning btn-sm" onclick="return confirm('Do you really want to approve this item?');">Approve</a></li>
                    @endif
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method="POST" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                @method('POST')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Quote ID"><b> {{ $quote_type_code }} Quote ID</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->id }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="CDB ID"><b> CDB ID</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->code }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Quote Status"><b> Quote Status</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->quote_status_text }}</p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="First Name"><b> First Name</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->first_name }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Last Name"><b> Last Name</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->last_name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Phone Number"><b> Phone Number</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->mobile_no }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Email Address"><b> Email Address</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->email }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Lang"><b> Lang</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->lang }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Source"><b style="width: 143.4px;"> Source</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center" style="width: 286.8px;word-wrap: break-word;">{{ $quote_request->source }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Reviver Name"><b> Reviver Name</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->reviver_name }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Advisor/Promo Code"><b> Promo Code</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->promo_code }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Device"><b> Device</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->device }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Payment Status"><b> Payment Status</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->payment_status_text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Reference Url"><b style="width: 143.4px;"> Reference Url</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center" style="width: 286.8px;word-wrap: break-word;">{{ $quote_request->reference_url }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Additional Notes"><b> Additional Notes</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->additional_notes }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Is Synced"><b> Is Synced</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->is_synced }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Customer ID"><b> Customer Name</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->cust_f_name }} {{ $quote_request->cust_l_name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Created At"><b> Created At</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->created_at }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Updated At"><b> Updated At</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->updated_at }}</p>
                            </div>
                        </div>
                    </div>
                    @if ($quote_type_code)
                    @if ($quote_type_code == 'Car')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Nationality"><b> Nationality</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->nationality_text }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="UAE licence held for"><b> UAE licence held for</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->uae_license_text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Car Make"><b> Car Make</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->car_make_text }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Car Model"><b> Car Model</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->car_model_text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Year of manufacture"><b> Year of manufacture</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->year_of_manufacture }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Emirate Of Registration"><b> Emirate Of Registration</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->emirates_text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Currently with"><b> Currently with</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->currently_insured_with }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Car value(AED)"><b> Car value(AED)</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->car_value }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Type Of Car Insurance"><b> Type Of Car Insurance</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->car_type_ins_text }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Claim History"><b> Claim History</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->claim_history_text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Date of Birth"><b> Date of Birth</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->dob }}</p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    @endif
                    @if ($quote_type_code == 'Health')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Cover for"><b>Cover for</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->health_cover_text }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Marital status"><b> Marital status</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->marital_status_text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Emirate of visa"><b> Emirate of visa</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->emirates_text }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Date of Birth"><b> Date of Birth</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->dob }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Gender"><b> Gender</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->gender }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Preferred hospitals/clinics"><b> Preferred hospitals/clinics</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->preference }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Details"><b> Details</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->details }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Optional covers required"><b> Optional covers required</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">
                                Dental Cover: {{ $quote_request->has_dental }}<br>
                                Worldwide Cover: {{ $quote_request->has_worldwide_cover }}<br>
                                Home Country Cover: {{ $quote_request->has_home }}<br>
                            </p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Details"><b> Nationality</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->nationality_text }}</p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    @endif
                    @if ($quote_type_code == 'Home')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="I am"><b> I am</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->home_possession_type_text }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="I live in"><b> I live in</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->home_accommodation_type_text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Cover for"><b> Cover for</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">
                                Contents(AED): {{ $quote_request->contents_aed }}<br>
                                Personal belongings(AED): {{ $quote_request->personal_belongings_aed }}<br>
                                Building(AED): {{ $quote_request->building_aed }}<br>
                            </p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Address"><b> Address</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->address }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if ($quote_type_code == 'Travel')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Days cover for"><b> Days cover for</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->days_cover_for }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Regions cover"><b> Regions cover</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->region_cover_text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Cover for"><b> Cover for</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->cover_for_text }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Details"><b> Details</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->details }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Nationality"><b> Nationality</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->nationality_text }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Destination"><b> Destination</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->destination }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if ($quote_type_code == 'Life')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Purpose of Insurance"><b> Purpose of Insurance</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->purpose_text }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Children"><b> Children</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->children_text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Marital status"><b> Marital status</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->marital_text }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Tenure of Insurance"><b> Tenure of Insurance</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->tenure_text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Smoker"><b> Smoker</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->is_smoker }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="No. of Years"><b> No. of Years</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->number_of_year_text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Sum Insured"><b> Sum Insured</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->currency_text }} {{ $quote_request->sum_insured_value }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Other Info"><b> Other Info</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->others_info }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Date of Birth"><b> Date of Birth</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->dob }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Gender"><b> Gender</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->gender }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Nationality"><b> Nationality</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->nationality_text }}</p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    @endif
                    @if ($quote_type_code == 'Bike')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Nationality"><b> Nationality</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->nationality_text }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Date of Birth"><b> Date of Birth</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->dob }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="UAE licence held for"><b> UAE licence held for</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->uae_license_text }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Bike(s) to insure"><b> Bike(s) to insure</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->bike_company_to_insure }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Bike value(AED)"><b> Bike value(AED)</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->bike_value }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Year of manufacture"><b> Year of manufacture</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->year_of_manufacture }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Currently with"><b> Currently with</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->currently_insured_with }}</p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    @endif
                    @if ($quote_type_code == 'Yacht')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Boat Details"><b> Boat Details</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->boat_details }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Engine Details"><b> Engine Details</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->engine_details }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Claims Experience"><b> Claims Experience</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->claim_experience }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Sum Insured"><b> Sum Insured</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->sum_insured_value }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Use"><b> Use</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->use }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Operator's Experience"><b> Operator's Experience</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->operator_experience }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if ($quote_type_code == 'Business')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Company Name"><b> Company Name</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->company_name }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Type of Business insurance"><b> Type of Business insurance</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->business_type_text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            @if ($business_type_code && $business_type_code != 'Photographers Insurance')
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Brief Details"><b> Brief Details</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->brief_details }}</p>
                            </div>
                            @endif
                        </div>
                        <div class="col">
                            @if ($business_type_code && $business_type_code == 'Group Medical')
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Number of Members"><b> Number of Members</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->number_of_employees }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            @if ($business_type_code && $business_type_code == 'Photographers Insurance')
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Interest"><b> Interest</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->interest }}</p>
                            </div>
                            @endif
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    @if ($quote_request->reference_url == 'crm.afia.ae')
                    @if ($business_type_code == 'Group Medical')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Contact Person Designation"><b> Contact Person Designation</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->contact_person_designation }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Renewal due date"><b> Renewal due date</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->renewal_due_date }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Cover type"><b> Cover type</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $business_cover_type_text }} </p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Time to contact"><b> Time to contact</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->time_to_contact }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Communication Mode preference"><b> Communication Mode preference</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $business_communication_mode_text }} </p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    @endif
                    @if ($business_type_code == 'Marine Hull (Yacht, Boat or Vessel)')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Boat Details"><b> Boat Details</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->boat_details }} </p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Engine details"><b> Engine details</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->engine_details }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Claims experience"><b> Claims experience</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->claims_experience }} </p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Sum Insured"><b> Sum Insured</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->sum_insured_value }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Use"><b> Use</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->use }} </p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Operator's Experience"><b> Operator's Experience</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quote_request->operators_experience }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endif
                    @endif
                    @endif
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            {{--<button type="submit" class="btn btn-warning btn-sm">Resubmit</button>--}}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>KYC-AML Logs</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Input</th>
                        <th>Screenshot</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                      </tr>
                    </thead>

                    <tbody>
                      @foreach($kyc_logs as $key => $kyc_log)
                      <tr>
                        <td><a href="/kyc/aml/{{$kyc_log->id}}">{{ $kyc_log->id }}</a></td>
                        <td>{{ $kyc_log->input }}</td>
                        <td><a href="{{ $kyc_log->screenshot }}" target="_blank"><img class="img-responsive" src="{{ $kyc_log->screenshot }}" alt="screenshot" height="80px" width="80px"></a></td>
                        <td>{{ $kyc_log->created_at }}</td>
                        <td>{{ $kyc_log->updated_at }}</td>
                      </tr>
                      @endforeach
                    </tbody>
                  </table>
            </div>
        </div>
    </div>
</div>
@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $quote_request->id }}" data-model="App\Models\AML">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection
