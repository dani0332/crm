@extends('layouts.app')
@section('title','Create Car Quote')
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('build/js/car_quote.js') }}"></script>
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
                <form id="car-create-quote-form" method='post' action="{{ route('CarPlanManualProcess') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                    {{csrf_field()}}
                    <input type="hidden" id="car_quote_uuid" name="car_quote_uuid" value="{{ $quoteUuId }}">
                    <input type="hidden" id="is_disabled" name="is_disabled" value="0">
                    <input type="hidden" id="is_create" name="is_create" value="1">
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6">
                            <div class="row">
                                <table cellspacing="5" cellpadding="5">
                                    <tr>
                                        <td>
                                            <select class="form-control" id='insurance_provider_id' name='insurance_provider_id' data-toggle="tooltip" data-placement="top" title="Please select insurance provider" style="width: 250px">
                                                <option value=''>Select Provider</option>
                                                @foreach($insuranceproviders as $insuranceprovider)
                                                @if (old('insurance_provider_id') == $insuranceprovider->id)
                                                <option value="{{ $insuranceprovider->id }}" selected>{{ $insuranceprovider->text }}</option>
                                                @else
                                                <option value="{{ $insuranceprovider->id }}">{{ $insuranceprovider->text }}</option>
                                                @endif
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-control" id="car_plan_id" name="car_plan_id" data-toggle="tooltip" data-placement="top" title="Please select plan" style="width: 250px">
                                                <option value="">Select Plan</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" id="actual_premium" name="actual_premium" placeholder="Enter Premium (without VAT)" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter premium (without VAT)" style="width: 160px" onkeypress="return isNumberKey(event,this)">
                                        </td>
                                        <td>
                                            <input type="number" id="car_value" name="car_value" placeholder="Enter Car Value" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter car value" style="width: 160px" onkeypress="return isNumberKey(event,this)">
                                        </td>
                                        <td>
                                            <input type="number" id="excess" name="excess" placeholder="Enter Excess" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter excess" style="width: 160px" onkeypress="return isNumberKey(event,this)">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <span style="color:red;" id="error-car-create-quote-form"></span> 
                            <button type="submit" class="btn btn-warning btn-sm">Create Quote</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection