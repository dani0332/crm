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

    <div class="modal fade" id="quotePlanModal" name="quotePlanModal" tabindex="-1" role="dialog" aria-labelledby="quotePlanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content" style="height: 40vw;">
                <div class="modal-header" style="border-bottom: none;">
                    <h5 class="modal-title" id="quotePlanModalLabel"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grid-3x3-gap-fill" viewBox="0 0 16 16">
                        <path d="M1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2zM1 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V7zM1 12a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-2z"/>
                      </svg> <strong>Plan Details</strong></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                </div>
                <div class="quote-plan-modal-body"> </div>
                <div class="modal-footer">
                        {{-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save changes</button> --}}
                </div>
            </div>
        </div>
    </div>
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
                                <th> </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($listQuotePlans as $key => $quotePlan)
                                <tr>
                                    <td>{{ ucwords($quotePlan->name) }}</td>
                                    {{-- <td><a href="{{ $record->id }}/plan_details/{{ $quotePlan->id }}" target="_blank">{{ ucwords($quotePlan->name) }}</td> --}}
                                    <td>{{ $quotePlan->providerName }}</td>
                                    <td>{{ $quotePlan->repairType }}</td>
                                    <td>{{ $quotePlan->actualPremium }}</td>
                                    <td>{{ $quotePlan->discountPremium }}</td>
                                    <td><a testurl="{{ $record->id }}/plan_details/{{ $quotePlan->id }}" class="btn btn-primary btn-sm m-2 quotePlanModalPopup" data-toggle="modal" data-target="#quotePlanModal">Details</a></td>
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
