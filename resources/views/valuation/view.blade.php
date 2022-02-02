@extends('layouts.app')
@section('title','View CarQuote')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-view">
        <div class="x_panel">
            <div class="x_title">
                <h2>Calculate Vehicle Valuation</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
            <form id="search-valuation" class="form-horizontal form-label-left"  role="form"  data-parsley-validate=""novalidate="">
                <div class="item form-group">
                    <span class="col-form-label col-md-2 col-sm-2">Car Make<span class="required">*</span></span>
                    <div class="col-md-6 col-sm-6">
                        <div class="input-group">
                            <select class="form-control" name="carmake" id="car_make_value" required>
                                <option value="">Select</option>
                                @foreach ($carMakes as $item)
                                    <option data-id="{{ $item->code }}" value="{{ $item->id }}">{{ $item->text }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <span class="col-form-label col-md-2 col-sm-2">Car Model<span class="required">*</span></span>
                    <div class="col-md-6 col-sm-6">
                        <div class="input-group">
                            <select class="form-control" name="carmodel" id="car_model_value">
                                <option value="">Select</option>
                            </select>
                        </div>
                    </div>
                </div>
                 <div class="item form-group">
                    <span class="col-form-label col-md-2 col-sm-2">Car Trim<span class="required">*</span></span>
                    <div class="col-md-6 col-sm-6">
                        <div class="input-group">
                            <select class="form-control" name="cartrim" id="car_trim_value">
                                <option value="">Select</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <span class="col-form-label col-md-2 col-sm-2">Year Of Manufacture<span class="required">*</span></span>
                    <div class="col-md-6 col-sm-6">
                        <div class="input-group">
                            <input type="text" class="form-control" type="number" value="{{ now()->year - 1 }}" name="yom" id="yom">
                        </div>
                    </div>
                </div>

                <div class="item form-group">
                    <div class="col-md-6 col-sm-6 ">
                        <div class="input-group">
                            <button id='calculateValuation' type="button" class="btn btn-warning">Calculate Depreciation</button>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6 ">
                        <div class="input-group">
                            <button id='reset' type="button" class="btn btn-success">Reset</button>
                        </div>
                    </div>
                </div>
                <div class="item form-group" id='result' style="display: none;">
                    <div class="col-md-6 col-sm-6">
                        <div class="input-group">
                            <b><label id="error" style='display: none;'></label></b>
                            <table class="table table-striped jambo_table " width="100%">
                                <thead>
                                    <th>Provider</th>
                                    <th>Car Value</th>
                                    <th>Car Value Upper Limit</th>
                                    <th>Car Value Lower Limit</th>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <b><label id="error" style='color: red;'></label></b>
            </form>
            </div>
        </div>
    </div>
</div>
@endsection
