@extends('layouts.app')
@section('title', $model->modelType.' Detail')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel">
                <div class="x_title">
                    <h2>{{$model->modelType.' Detail'}}</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('quotes/'.strtolower($model->modelType)) }}" class="btn btn-warning btn-sm">{{$model->modelType.' List'}}</a></li>
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
                    @foreach($model->properties as $property => $value)
                        <div class="item form-group">
                            @if(strpos($value, 'title'))
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Status Description"><b>{{ strtoupper($customTitles[$property])}}</b></label>
                            @else
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Status Description"><b>{{str_replace("_"," ",strtoupper($property))}}</b></label>
                            @endif
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ $record[$property] }}</p>
                            </div>
                        </div>
                    @endforeach
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <a id="texta" href="{{ url('quotes/'.strtolower($model->modelType).'/'.$record->id.'/edit') }}" class='btn btn-warning btn-sm'>Edit</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($model->modelType == "Car")
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Available Plans</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <br />
                    <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                        <thead>
                            <tr>
                                <th>Plan Name</th>
                                <th>Provider Name</th>
                                <th>Repair Type</th>
                                <th>Actual Premium</th>
                                <th>Discount Premium</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($listQuotePlans as $key => $quotePlan)
                                <tr>
                                    <td><a href="{{ $record->id }}/plan_details/{{ $quotePlan->id }}" target="_blank">{{ ucwords($quotePlan->name) }}</td>
                                    <td>{{ $quotePlan->providerName }}</td>
                                    <td>{{ $quotePlan->repairType }}</td>
                                    <td>{{ $quotePlan->actualPremium }}</td>
                                    <td>{{ $quotePlan->discountPremium }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
