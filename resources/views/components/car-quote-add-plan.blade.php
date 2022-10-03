@extends('layouts.app')
@section('title','Create Car Quote')
@section('content')
@php
    use App\Enums\GenericRequestEnum;
    use App\Enums\CarPlanType;
@endphp
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('build/js/car_quote.js') }}"></script>
<link href="{{ asset('build/css/car_quote.css') }}" rel="stylesheet">
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create Car Quote</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('quotes/car/'.$quoteUuId.'') }}" class="btn btn-warning btn-sm">Go back</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                @if(session()->has('message'))
                <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <form id="car-create-quote-form" method='post' action="car-plan-manual-process" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                    {{csrf_field()}}
                    <input type="hidden" id="car_quote_uuid" name="car_quote_uuid" value="{{ $quoteUuId }}">
                    <input type="hidden" id="is_disabled" name="is_disabled" value="0">
                    <input type="hidden" id="is_create" name="is_create" value="1">
                    <input type="hidden" id="repair_type_comp" name="repair_type_comp" value="{{ CarPlanType::COMP }}">
                    <input type = "hidden" id="current_url" name="current_url" value="{{ url()->current() }}">
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6">
                            <div class="row">
                                <table cellspacing="5" cellpadding="5">
                                    <tr>
                                        <td>
                                            <select class="form-control" id='insurance_provider_id' name='insurance_provider_id' data-toggle="tooltip" data-placement="top" title="Please select insurance provider">
                                                <option value=''>Select Provider</option>
                                                @foreach($insuranceProviders as $insuranceProvider)
                                                    @if (old('insurance_provider_id') == $insuranceProvider->id)
                                                        <option value="{{ $insuranceProvider->id }}" selected>{{ $insuranceProvider->text }}</option>
                                                    @else
                                                        <option value="{{ $insuranceProvider->id }}">{{ $insuranceProvider->text }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-control" id="car_plan_id" name="car_plan_id" data-toggle="tooltip" data-placement="top" title="Please select plan">
                                                <option value="">Select Plan</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" id="actual_premium" name="actual_premium" placeholder="Enter Premium (without VAT)" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter premium (without VAT)" onkeypress="return isNumberKey(event,this)">
                                        </td>
                                        <td>
                                            <input type="text" id="car_value" name="car_value" placeholder="Enter Car Value" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter car value" onkeypress="return isNumberKey(event,this)">
                                        </td>
                                        <td>
                                            <input type="number" id="excess" name="excess" placeholder="Enter Excess" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter excess" onkeypress="return isNumberKey(event,this)">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <span id="error-car-create-quote-form"></span> 
                            <button type="submit" class="btn btn-warning btn-sm" id="car-quote-create-btn">Create Quote</button>
                        </div>
                    </div>
                </form>
                <br>
                <h2>Quoted Plans</h2>
                <table class="table table-striped jambo_table existing-plans">
                    <thead>
                        <tr>
                            <th width="350px">Provider Name</th>
                            <th>Plan Name</th>
                            <th>Repair Type</th>
                            <th>Premium with VAT.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(gettype($listQuotePlans) != GenericRequestEnum::TypeString)
                        @forelse ($listQuotePlans as $key => $quotePlan)
                            @if(!isset($quotePlan->id))
								@continue;
							@endif
                        <tr>
                            <td>{{ ucwords($quotePlan->providerName) }}</td>
                            <td>{{ ucwords($quotePlan->name) }}</td>
                            <td>{{ $quotePlan->repairType == CarPlanType::COMP ? 'NON-AGENCY' : $quotePlan->repairType }}</td>
                            <td>
                                @php
                                    $totalSelectedAddonsPriceWithVat = 0;
                                    foreach ($quotePlan->addons as $quotePlanAddon) {
                                        foreach ($quotePlanAddon->carAddonOption as $quotePlanOptions) {
                                            if (isset($quotePlanOptions->isSelected)) {
                                                if ($quotePlanOptions->isSelected == true && $quotePlanOptions->price != 0) {
                                                    $totalSelectedAddonsPriceWithVat += $quotePlanOptions->price + $quotePlanOptions->vat;
                                                }
                                            } else {
                                                $totalSelectedAddonsPriceWithVat = 0;
                                            }
                                        }
                                    }
                                    $totalPremium = $quotePlan->discountPremium + $quotePlan->vat + $totalSelectedAddonsPriceWithVat;
                                @endphp
                                {{ $totalPremium ? number_format($totalPremium, 2) : '0.00' }}
                            </td>
                        </tr>
                        @empty
                                <tr class="odd">
                                    <td valign="top" colspan="4" class="dataTables_empty">No data available in table</td>
                                </tr>
                        @endforelse
                        @else
                            <tr class="odd">
                                <td valign="top" colspan="4" class="dataTables_empty">No data available in table</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection