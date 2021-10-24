@extends('layouts.app')
@section('title', $quoteTypeText)
@section('content')
    <?php
    use App\Enums\quoteTypeCode;
    use App\Enums\quoteStatusCode;
    use App\Enums\quoteBusinessTypeCode;
    ?>
    <div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel">
                <div class="x_title">
                    <h2>{{ $quoteTypeText }} Quote</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ route('aml.index') }}" class="btn btn-warning btn-sm">All Quotes</a></li>
                        @if ($quoteStatusCode == quoteStatusCode::AMLScreeningFailed || $quoteStatusCode == quoteStatusCode::AMLScreeningCleared)
                            @if ($resultsFound > 0 && $isCurrentUserFromCompliance == 1)
                                <li><a href="{{ $quoteRequest->id }}/quoteStatusUpdate/{{ quoteStatusCode::AMLScreeningFailed }}"
                                class='btn btn-danger btn-sm'
                                onclick="return confirm('Do you want to update status to AML Screening Failed?');">Fail</a></li>
                                <li><a href="{{ $quoteRequest->id }}/quoteStatusUpdate/{{ quoteStatusCode::AMLScreeningCleared }}"
                                class="btn btn-success btn-sm"
                                onclick="return confirm('Do you want to update status to AML Screening Cleared?');">Pass</a></li>
                            @endif
                            @if ($resultsFound == 0 && $isCurrentUserFromPaAml == 1 && $getAMLNumRows >= 2)
                                <li><a href="#" class="btn btn-success btn-sm"
                                style="opacity: .4;cursor: default !important;pointer-events: none;">Pass</a></li>
                            @endif
                        @else
                            @if ($resultsFound > 0 && $isCurrentUserFromCompliance == 1)
                                <li><a href="{{ $quoteRequest->id }}/quoteStatusUpdate/{{ quoteStatusCode::AMLScreeningFailed }}"
                                class='btn btn-danger btn-sm'
                                onclick="return confirm('Do you want to update status to AML Screening Failed?');">Fail</a></li>
                                <li><a href="{{ $quoteRequest->id }}/quoteStatusUpdate/{{ quoteStatusCode::AMLScreeningCleared }}"
                                class="btn btn-success btn-sm"
                                onclick="return confirm('Do you want to update status to AML Screening Cleared?');">Pass</a></li>
                            @endif
                            @if ($resultsFound == 0 && $isCurrentUserFromPaAml == 1 && $getAMLNumRows >= 2)
                                <li><a href="{{ $quoteRequest->id }}/quoteStatusUpdate/{{ quoteStatusCode::AMLScreeningCleared }}"
                                class="btn btn-success btn-sm"
                                onclick="return confirm('Do you want to update status to AML Screening Cleared?');">Pass</a></li>
                            @endif
                        @endif
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <br />
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    @if ($resultsFound > 0 && $isCurrentUserFromPaAml == 1 && $getAMLNumRows >= 2)
                    <div class="required" style="text-align: center;"><p><b>Matches found. Please check with Compliance.</b></p></div>
                    @endif
                    <form id="demo-form2" method="POST" action="{{ $quoteRequest->id }}/quoteUpdate"
                        enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left"
                        autocomplete="off">
                        {{ csrf_field() }}
                        @method('GET')
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Quote ID"><b>
                                        {{ $quoteTypeCode }} Quote ID</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->id }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="CDB ID"><b> CDB
                                        ID</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->code }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Quote Status"><b> Quote
                                        Status</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->quote_status_text }}</p>
                                </div>
                            </div>
                            <div class="col">

                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="First Name"><b> First
                                        Name</b> <span class="required">*</span></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">
                                        <input type="text" id="first_name" name="first_name"
                                            value="{{ old('first_name', ucwords(strtolower($quoteRequest->first_name))) }}"
                                            class="form-control" data-toggle="tooltip" data-placement="top"
                                            title="Please enter first name">
                                        @if ($errors->has('first_name'))
                                            <span class="text-danger">{{ $errors->first('first_name') }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Last Name"><b>Last Name</b> <span class="required">*</span></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">
                                        <input type="text" id="last_name" name="last_name"
                                            value="{{ old('last_name', ucwords(strtolower($quoteRequest->last_name))) }}"
                                            class="form-control" data-toggle="tooltip" data-placement="top"
                                            title="Please enter last name">
                                        @if ($errors->has('last_name'))
                                            <span class="text-danger">{{ $errors->first('last_name') }}</span>
                                        @endif
                                    </p>
                                    <div style="text-align: right;"><button type="submit" class="btn btn-primary btn-sm" id="return_to_view">Update & Verify</button></div>
                            </div>
                        </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Phone Number"><b> Phone
                                Number</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quoteRequest->mobile_no }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Email Address"><b> Email
                                Address</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quoteRequest->email }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Lang"><b> Lang</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quoteRequest->lang }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Source"><b style="width: 143.4px;">
                                Source</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center" style="width: 286.8px;word-wrap: break-word;">
                                {{ $quoteRequest->source }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Reviver Name"><b> Reviver
                                Name</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quoteRequest->reviver_name }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Advisor/Promo Code"><b> Promo
                                Code</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quoteRequest->promo_code }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Device"><b> Device</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quoteRequest->device }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Payment Status"><b> Payment
                                Status</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quoteRequest->payment_status_text }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Reference Url"><b
                                style="width: 143.4px;"> Reference Url</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center" style="width: 286.8px;word-wrap: break-word;">
                                {{ $quoteRequest->reference_url }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Additional Notes"><b> Additional
                                Notes</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quoteRequest->additional_notes }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Is Synced"><b> Is
                                Synced</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quoteRequest->is_synced }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Customer ID"><b> Customer
                                Name</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quoteRequest->cust_f_name }}
                                {{ $quoteRequest->cust_l_name }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Created At"><b> Created
                                At</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quoteRequest->created_at }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Updated At"><b> Updated
                                At</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $quoteRequest->updated_at }}</p>
                        </div>
                    </div>
                </div>
                @if ($quoteTypeCode)
                    @if ($quoteTypeCode == quoteTypeCode::Car)
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Nationality"><b>
                                        Nationality</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->nationality_text }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="UAE licence held for"><b>
                                        UAE licence held for</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->uae_license_text }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Car Make"><b> Car
                                        Make</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->car_make_text }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Car Model"><b> Car
                                        Model</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->car_model_text }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Year of manufacture"><b>
                                        Year of manufacture</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->year_of_manufacture }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align"
                                    for="Emirate Of Registration"><b> Emirate Of Registration</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->emirates_text }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Currently with"><b>
                                        Currently with</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->currently_insured_with }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Car value(AED)"><b> Car
                                        value(AED)</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->car_value }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Type Of Car Insurance"><b>
                                        Type Of Car Insurance</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->car_type_ins_text }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Claim History"><b> Claim
                                        History</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->claim_history_text }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Date of Birth"><b> Date of
                                        Birth</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->dob }}</p>
                                </div>
                            </div>
                            <div class="col">

                            </div>
                        </div>
                    @endif
                    @if ($quoteTypeCode == quoteTypeCode::Health)
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Cover for"><b>Cover
                                        for</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->health_cover_text }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Marital status"><b>
                                        Marital status</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->marital_status_text }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Emirate of visa"><b>
                                        Emirate of visa</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->emirates_text }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Date of Birth"><b> Date of
                                        Birth</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->dob }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Gender"><b>
                                        Gender</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->gender }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align"
                                    for="Preferred hospitals/clinics"><b> Preferred hospitals/clinics</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->preference }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Details"><b>
                                        Details</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->details }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align"
                                    for="Optional covers required"><b> Optional covers required</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">
                                        Dental Cover: {{ $quoteRequest->has_dental }}<br>
                                        Worldwide Cover: {{ $quoteRequest->has_worldwide_cover }}<br>
                                        Home Country Cover: {{ $quoteRequest->has_home }}<br>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Details"><b>
                                        Nationality</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->nationality_text }}</p>
                                </div>
                            </div>
                            <div class="col">

                            </div>
                        </div>
                    @endif
                    @if ($quoteTypeCode == quoteTypeCode::Home)
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="I am"><b> I am</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->home_possession_type_text }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="I live in"><b> I live
                                        in</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->home_accommodation_type_text }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Cover for"><b> Cover
                                        for</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">
                                        Contents(AED): {{ $quoteRequest->contents_aed }}<br>
                                        Personal belongings(AED): {{ $quoteRequest->personal_belongings_aed }}<br>
                                        Building(AED): {{ $quoteRequest->building_aed }}<br>
                                    </p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Address"><b>
                                        Address</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->address }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($quoteTypeCode == quoteTypeCode::Travel)
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Days cover for"><b> Days
                                        cover for</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->days_cover_for }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Regions cover"><b> Regions
                                        cover</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->region_cover_text }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Cover for"><b> Cover
                                        for</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->cover_for_text }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Details"><b>
                                        Details</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->details }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Nationality"><b>
                                        Nationality</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->nationality_text }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Destination"><b>
                                        Destination</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->destination }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($quoteTypeCode == quoteTypeCode::Life)
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Purpose of Insurance"><b>
                                        Purpose of Insurance</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->purpose_text }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Children"><b>
                                        Children</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->children_text }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Marital status"><b>
                                        Marital status</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->marital_text }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Tenure of Insurance"><b>
                                        Tenure of Insurance</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->tenure_text }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Smoker"><b>
                                        Smoker</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->is_smoker }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="No. of Years"><b> No. of
                                        Years</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->number_of_year_text }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Sum Insured"><b> Sum
                                        Insured</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->currency_text }}
                                        {{ $quoteRequest->sum_insured_value }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Other Info"><b> Other
                                        Info</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->others_info }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Date of Birth"><b> Date of
                                        Birth</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->dob }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Gender"><b>
                                        Gender</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->gender }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Nationality"><b>
                                        Nationality</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->nationality_text }}</p>
                                </div>
                            </div>
                            <div class="col">

                            </div>
                        </div>
                    @endif
                    @if ($quoteTypeCode == quoteTypeCode::Bike)
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Nationality"><b>
                                        Nationality</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->nationality_text }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Date of Birth"><b> Date of
                                        Birth</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->dob }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="UAE licence held for"><b>
                                        UAE licence held for</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->uae_license_text }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Bike(s) to insure"><b>
                                        Bike(s) to insure</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->bike_company_to_insure }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Bike value(AED)"><b> Bike
                                        value(AED)</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->bike_value }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Year of manufacture"><b>
                                        Year of manufacture</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->year_of_manufacture }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Currently with"><b>
                                        Currently with</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->currently_insured_with }}</p>
                                </div>
                            </div>
                            <div class="col">

                            </div>
                        </div>
                    @endif
                    @if ($quoteTypeCode == quoteTypeCode::Yacht)
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Boat Details"><b> Boat
                                        Details</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->boat_details }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Engine Details"><b> Engine
                                        Details</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->engine_details }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Claims Experience"><b>
                                        Claims Experience</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->claim_experience }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Sum Insured"><b> Sum
                                        Insured</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->sum_insured_value }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Use"><b> Use</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->use }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Operator's Experience"><b>
                                        Operator's Experience</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->operator_experience }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($quoteTypeCode == quoteTypeCode::Business)
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="Company Name"><b> Company
                                        Name</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->company_name }}</p>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align"
                                    for="Type of Business insurance"><b> Type of Business insurance</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $quoteRequest->business_type_text }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                @if ($businessTypeCode && $businessTypeCode != quoteBusinessTypeCode::photographers)
                                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="Brief Details"><b>
                                            Brief Details</b></label>
                                    <div class="col-md-6 col-sm-6">
                                        <p class="label-align-center">{{ $quoteRequest->brief_details }}</p>
                                    </div>
                                @endif
                            </div>
                            <div class="col">
                                @if ($businessTypeCode && $businessTypeCode == quoteBusinessTypeCode::groupMedical)
                                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="Number of Members"><b>
                                            Number of Members</b></label>
                                    <div class="col-md-6 col-sm-6">
                                        <p class="label-align-center">{{ $quoteRequest->number_of_employees }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                @if ($businessTypeCode && $businessTypeCode == quoteBusinessTypeCode::photographers)
                                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="Interest"><b>
                                            Interest</b></label>
                                    <div class="col-md-6 col-sm-6">
                                        <p class="label-align-center">{{ $quoteRequest->interest }}</p>
                                    </div>
                                @endif
                            </div>
                            <div class="col">

                            </div>
                        </div>
                        @if ($quoteRequest->reference_url == 'crm.afia.ae')
                            @if ($businessTypeCode == quoteBusinessTypeCode::groupMedical)
                                <div class="item form-group">
                                    <div class="col">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align"
                                            for="Contact Person Designation"><b> Contact Person Designation</b></label>
                                        <div class="col-md-6 col-sm-6">
                                            <p class="label-align-center">
                                                {{ $quoteRequest->contact_person_designation }}</p>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align"
                                            for="Renewal due date"><b> Renewal due date</b></label>
                                        <div class="col-md-6 col-sm-6">
                                            <p class="label-align-center">{{ $quoteRequest->renewal_due_date }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="item form-group">
                                    <div class="col">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Cover type"><b>
                                                Cover type</b></label>
                                        <div class="col-md-6 col-sm-6">
                                            <p class="label-align-center">{{ $businessCoverTypeText }} </p>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align"
                                            for="Time to contact"><b> Time to contact</b></label>
                                        <div class="col-md-6 col-sm-6">
                                            <p class="label-align-center">{{ $quoteRequest->time_to_contact }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="item form-group">
                                    <div class="col">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align"
                                            for="Communication Mode preference"><b> Communication Mode
                                                preference</b></label>
                                        <div class="col-md-6 col-sm-6">
                                            <p class="label-align-center">{{ $businessCommuModeText }} </p>
                                        </div>
                                    </div>
                                    <div class="col">

                                    </div>
                                </div>
                            @endif
                            @if ($businessTypeCode == quoteBusinessTypeCode::marineHull)
                                <div class="item form-group">
                                    <div class="col">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Boat Details"><b>
                                                Boat Details</b></label>
                                        <div class="col-md-6 col-sm-6">
                                            <p class="label-align-center">{{ $quoteRequest->boat_details }} </p>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align"
                                            for="Engine details"><b> Engine details</b></label>
                                        <div class="col-md-6 col-sm-6">
                                            <p class="label-align-center">{{ $quoteRequest->engine_details }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="item form-group">
                                    <div class="col">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align"
                                            for="Claims experience"><b> Claims experience</b></label>
                                        <div class="col-md-6 col-sm-6">
                                            <p class="label-align-center">{{ $quoteRequest->claims_experience }} </p>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Sum Insured"><b>
                                                Sum Insured</b></label>
                                        <div class="col-md-6 col-sm-6">
                                            <p class="label-align-center">{{ $quoteRequest->sum_insured_value }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="item form-group">
                                    <div class="col">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Use"><b>
                                                Use</b></label>
                                        <div class="col-md-6 col-sm-6">
                                            <p class="label-align-center">{{ $quoteRequest->use }} </p>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align"
                                            for="Operator's Experience"><b> Operator's Experience</b></label>
                                        <div class="col-md-6 col-sm-6">
                                            <p class="label-align-center">{{ $quoteRequest->operators_experience }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endif
                @endif
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
                                <th>AML Id</th>
                                <th>Input</th>
                                <th>Screenshot</th>
                                <th>Match Found</th>
                                <th>Results Found</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($kycLogs as $key => $kycLog)
                                <tr>
                                    <td><a href="/kyc/aml/{{ $kycLog->id }}">{{ $kycLog->id }}</a></td>
                                    <td>{{ $kycLog->input }}</td>
                                    <td><a href="{{ $kycLog->screenshot }}" target="_blank"><img class="img-responsive"
                                                src="{{ $kycLog->screenshot }}" alt="screenshot" height="80px"
                                                width="80px"></a></td>
                                    <td>@if ($kycLog->results_found > 0) True @else False @endif </td>
                                    <td>{{ $kycLog->results_found }}</td>
                                    <td>{{ $kycLog->created_at }}</td>
                                    <td>{{ $kycLog->updated_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @can('aml-audit')
        <div id="auditable">
            <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $quoteRequest->id }}"
                data-model="App\Models\{{ $auditLogLine }}">
                View Audit Logs
            </button>
        </div>
    @endcan
@endsection
