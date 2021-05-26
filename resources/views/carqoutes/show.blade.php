@extends('layouts.app')
@section('title','Car Qoute Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Car Qoute Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><button id="resubmit_api_carqoute" class="btn btn-success btn-sm" data-id="{{ $carqoute->id }}">ReSubmit Api</button></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div id="success_message" class="alert alert-success" style="display:none"></div>
                <div id="error_message" class="alert alert-danger" style="display:none"></div>
                <br />
                <table class="table table-striped">
                    <tr>
                        <td>
                            Id 
                        </td>
                        <td>
                            {{ $carqoute->id }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Car Value
                        </td>
                        <td>
                            {{ $carqoute->car_value }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Currently Insured With
                        </td>
                        <td>
                            {{ $carqoute->currently_insured_with }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Year Of Manufacture
                        </td>
                        <td>
                            {{ $carqoute->year_of_manufacture }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            UAE License Held For
                        </td>
                        <td>
                            {{ $carqoute->uaeLicenseHeldFor->code }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Car Make
                        </td>
                        <td>
                            {{ $carqoute->carMake->code  }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Car Model
                        </td>
                        <td>
                            {{ $carqoute->carModel->code }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Car Value
                        </td>
                        <td>
                            {{ $carqoute->car_value }}
                        </td>
                    </tr>
                    
                    <tr>
                        <td>
                            Emirate Of Registration
                        </td>
                        <td>
                            {{ $carqoute->emirate->code }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            First Name 
                        </td>
                        <td>
                            {{ $carqoute->first_name }}
                        </td>
                    </tr>
                     
                    <tr>
                        <td>
                            Last Name 
                        </td>
                        <td>
                            {{ $carqoute->last_name }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Email 
                        </td>
                        <td>
                            {{ $carqoute->email }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Claim History
                        </td>
                        <td>
                            {{ $carqoute->claimHistory->code }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Car Type Insurance 
                        </td>
                        <td>
                            {{ $carqoute->carTypeInsurance->code }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Mobile No
                        </td>
                        <td>
                            {{ $carqoute->mobile_no }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                           Gender 
                        </td>
                        <td>
                            {{ $carqoute->gender }}
                        </td>
                    </tr>


                    <tr>
                        <td>
                            Lang 
                        </td>
                        <td>
                            {{ $carqoute->lang }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Source 
                        </td>
                        <td>
                            {{ $carqoute->source }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            DOB 
                        </td>
                        <td>
                            {{ $carqoute->dob }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Customer 
                        </td>
                        <td>
                            {{ $carqoute->customer->first_name .' '. $carqoute->customer->last_name }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                           Nationality 
                        </td>
                        <td>
                            {{ $carqoute->nationality->code }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Payment Status
                        </td>
                        <td>
                            {{ $carqoute->paymentStatus->code }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Quote Status Id
                        </td>
                        <td>
                            {{ $carqoute->quoteStatus->code }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Is Synced
                        </td>
                        <td>
                            {{ $carqoute->is_synced }}
                        </td>
                    </tr>
                    
                    <tr>
                        <td>
                            Device
                        </td>
                        <td>
                            {{ $carqoute->device }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Reference Url
                        </td>
                        <td>
                            {{ $carqoute->reference_url }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Additional Notes
                        </td>
                        <td>
                            {{ $carqoute->additional_notes }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Reviver Name
                        </td>
                        <td>
                            {{ $carqoute->reviver_name }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Promo Code
                        </td>
                        <td>
                            {{ $carqoute->promo_code }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Code
                        </td>
                        <td>
                            {{ $carqoute->code }}
                        </td>
                    </tr>
                    
                    
                    
                </table >
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection