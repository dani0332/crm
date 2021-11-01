@extends('layouts.app')
@section('title', 'Plan Details')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Plan Details - {{ ucwords($listQuotePlanName) }}</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url()->previous() }}" class="btn btn-warning btn-sm">Go Back</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <table cellpadding="3" cellspacing="3">
                        <tr><td style="width: 150px;">Provider Code:</td> <td>{{ $providerCode }}</td></tr>
                        <tr><td>Provider Name:</td> <td>{{ $providerName }}</td></tr>
                        <tr><td>Repair Type:</td> <td>{{ $repairType }}</td></tr>
                        <tr><td>Actual Premium:</td> <td>{{ $actualPremium }}</td></tr>
                        <tr><td>Discount Premium:</td> <td>{{ $discountPremium }}</td></tr>
                    </table>
                    <br />
                    <p>
                        <p><h5>Addons</h5></p>
                        <table cellpadding="3" cellspacing="3">
                            <tr>
                                <td>
                                    <table cellpadding="3" cellspacing="3">
                                        @foreach ($listQuotePlanAddons as $key => $listQuotePlanAddon)
                                            <tr><td style="width: 150px;">{{ ucwords($listQuotePlanAddon->text) }}</td></tr>
                                        @endforeach
                                    </table>
                                </td>
                                <td>
                                    <table cellpadding="3" cellspacing="3">
                                        @foreach ($listQuotePlanAddonValues as $key => $listQuotePlanAddonValue)
                                            <tr><td style="width: 350px;">{{ ucwords($listQuotePlanAddonValue) }}</td></tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </p>
                    <br />
                    <p>
                        <p><h5>Benefits - Inclusion</h5></p>
                        <table cellpadding="3" cellspacing="3">
                            <tr>
                                <td>
                                    <table cellpadding="3" cellspacing="3">
                                        @foreach ($listQuotePlanBenefitsInclusions as $key => $listQuotePlanBenefitsInclusion)
                                            <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsInclusion->text) }}</td>
                                                <td>{{ ucwords($listQuotePlanBenefitsInclusion->value) }}</td></tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </p>
                    <br />
                    <p>
                        <p><h5>Benefits - Exclusion</h5></p>
                        <table cellpadding="3" cellspacing="3">
                            <tr>
                                <td>
                                    <table cellpadding="3" cellspacing="3">
                                        @foreach ($listQuotePlanBenefitsExclusions as $key => $listQuotePlanBenefitsExclusion)
                                            <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsExclusion->text) }}</td>
                                                <td>{{ ucwords($listQuotePlanBenefitsExclusion->value) }}</td></tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </p>
                    <br />
                    <p>
                        <p><h5>Features</h5></p>
                        <table cellpadding="3" cellspacing="3">
                            <tr>
                                <td>
                                    <table cellpadding="3" cellspacing="3">
                                        @foreach ($listQuotePlanBenefitsFeatures as $key => $listQuotePlanBenefitsFeature)
                                            <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsFeature->text) }}</td>
                                                <td>{{ ucwords($listQuotePlanBenefitsFeature->value) }}</td></tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </p>
                    <br />
                    <p>
                        <p><h5>Road Side Assistance</h5></p>
                        <table cellpadding="3" cellspacing="3">
                            <tr>
                                <td>
                                    <table cellpadding="3" cellspacing="3">
                                        @foreach ($listQuotePlanBenefitsRsas as $key => $listQuotePlanBenefitsRsa)
                                            <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsRsa->text) }}</td>
                                                <td>{{ ucwords($listQuotePlanBenefitsRsa->value) }}</td></tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </p>
                    <br />
                    <p>
                        <p><h5>Policy Detail</h5></p>
                        <table cellpadding="3" cellspacing="3">
                            <tr>
                                <td>
                                    <table cellpadding="3" cellspacing="3">
                                        @foreach ($listQuotePlanBenefitsPolicyDetails as $key => $listQuotePlanBenefitsPolicyDetail)
                                            <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsPolicyDetail->text) }}</td>
                                                <td>{{ ucwords($listQuotePlanBenefitsPolicyDetail->value) }}</td></tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </p>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            {{-- <a id="texta" href="{{ url('quotes/'.strtolower($model->modelType).'/'.$record->id.'/edit') }}" class='btn btn-warning btn-sm'>Edit</a> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
