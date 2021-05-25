@extends('layouts.app')
@section('title','Health Qoute Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Health Qoute Detail</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <table class="table table-striped">
                    <tr>
                        <td>
                            Id 
                        </td>
                        <td>
                            {{ $healthqoute->id }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Preference
                        </td>
                        <td>
                            {{ $healthqoute->preference }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Details
                        </td>
                        <td>
                            {{ $healthqoute->details }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Has Dental
                        </td>
                        <td>
                            {{ $healthqoute->has_dental }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Has Worldwide Cover
                        </td>
                        <td>
                            {{ $healthqoute->has_worldwide_cover }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Has Home
                        </td>
                        <td>
                            {{ $healthqoute->has_home  }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Marital Status
                        </td>
                        <td>
                            {{ $healthqoute->marital_status_id }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Cover For
                        </td>
                        <td>
                            {{ $healthqoute->cover_for_id }}
                        </td>
                    </tr>
                    
                    <tr>
                        <td>
                            Emirate Of Registration
                        </td>
                        <td>
                            {{ $healthqoute->emirate->code }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            First Name 
                        </td>
                        <td>
                            {{ $healthqoute->first_name }}
                        </td>
                    </tr>
                     
                    <tr>
                        <td>
                            Last Name 
                        </td>
                        <td>
                            {{ $healthqoute->last_name }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Email 
                        </td>
                        <td>
                            {{ $healthqoute->email }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Mobile No
                        </td>
                        <td>
                            {{ $healthqoute->mobile_no }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                           Gender 
                        </td>
                        <td>
                            {{ $healthqoute->gender }}
                        </td>
                    </tr>


                    <tr>
                        <td>
                            Lang 
                        </td>
                        <td>
                            {{ $healthqoute->lang }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Source 
                        </td>
                        <td>
                            {{ $healthqoute->source }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            DOB 
                        </td>
                        <td>
                            {{ $healthqoute->dob }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Customer 
                        </td>
                        <td>
                            {{ $healthqoute->customer->first_name .' '. $healthqoute->customer->last_name }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                           Nationality 
                        </td>
                        <td>
                            {{ $healthqoute->nationality->code }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Payment Status
                        </td>
                        <td>
                            {{ $healthqoute->paymentStatus->code }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Quote Status Id
                        </td>
                        <td>
                            {{ $healthqoute->quoteStatus->code }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Is Synced
                        </td>
                        <td>
                            {{ $healthqoute->is_synced }}
                        </td>
                    </tr>
                    
                    <tr>
                        <td>
                            Device
                        </td>
                        <td>
                            {{ $healthqoute->device }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Reference Url
                        </td>
                        <td>
                            {{ $healthqoute->reference_url }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Additional Notes
                        </td>
                        <td>
                            {{ $healthqoute->additional_notes }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Reviver Name
                        </td>
                        <td>
                            {{ $healthqoute->reviver_name }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Promo Code
                        </td>
                        <td>
                            {{ $healthqoute->promo_code }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Code
                        </td>
                        <td>
                            {{ $healthqoute->code }}
                        </td>
                    </tr>
                    
                    
                    
                </table >
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection