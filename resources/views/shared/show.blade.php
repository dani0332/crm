@extends('layouts.app')
@section('title', $model->modelType.' Detail')
@section('content')
<style>
#quote-plans table.dataTable thead .sorting_asc:after {
    content: none !important;
}
</style>
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
                            @if(str_contains($value, 'select'))
                                @if(str_contains($value, 'customTable'))
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $customTableList[$property][0]->names }}</p>
                                </div>
                                @else
                                    <div class="col-md-6 col-sm-6">
                                        @php
                                            $propertyName = $property.'_text';
                                        @endphp
                                        <p class="label-align-center">{{ $record[0]->$propertyName}}</p>
                                    </div>
                                @endif
                            @else
                                @if(str_contains($value, 'customTable'))
                                <div class="col-md-6 col-sm-6">
                                    <p class="label-align-center">{{ $customTableList[$property][0]->names }}</p>
                                </div>
                                @else
                                    <div class="col-md-6 col-sm-6">
                                        <p class="label-align-center">{{ $record[0]->$property }}</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <a id="texta" href="{{ url('quotes/'.strtolower($model->modelType).'/'.$record[0]->id.'/edit') }}" class='btn btn-warning btn-sm'>Edit</a>
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
                    <h5 class="modal-title" id="quotePlanModalLabel"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grid-3x3-gap-fill" viewBox="0 0 16 16" style="vertical-align: unset;">
                        <path d="M1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2zM1 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V7zM1 12a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-2z"/>
                      </svg> <strong>Plan Details</strong></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                </div>
                <div class="quote-plan-modal-body"> </div>
                <div class="modal-footer" style="border: none">
                        {{-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save changes</button> --}}
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="#" class="form-horizontal form-label-left" role="form" data-parsley-validate=""novalidate="" autocomplete="off">
        {{csrf_field()}}
        @method('GET')
        <div class="row">
            <div class="col-md-12 col-sm-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Available Plans</h2>
                        <div class="clearfix"></div>
                    </div>
                    @if(gettype($listQuotePlans) != 'string')
                    <div class="x_content">
                        <div class="row">
                            <div class="col-auto mr-auto"></div>
                            <div class="col-auto">
                                <input type="hidden" id="selectquoteUuId" name="selectquoteUuId" value="{{ $quoteAttrUuId }}">
                                <button type="button" id="quotePlansGenerateButton" name="quotePlansGenerateButton" class="btn btn-warning btn-sm">Generate Quote</button>
                            </div>
                        </div>
                        <div id="quote-plans">
                            <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Provider Name</th>
                                        <th>Plan Name</th>
                                        <th>Repair Type</th>
                                        <th>Actual Premium</th>
                                        <th>Premium with VAT</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($listQuotePlans as $key => $quotePlan)
                                        <tr>
                                            <td>{{ ucwords($quotePlan->providerName) }}</td>
                                            <td>{{ ucwords($quotePlan->name) }}</td>
                                            <td>{{ $quotePlan->repairType }}</td>
                                            <td>{{ $quotePlan->actualPremium }}</td>
                                            <td>{{ $quotePlan->actualPremium + $quotePlan->vatPremium }}</td>
                                            <td><a href="#" testurl="{{ $record[0]->id }}/plan_details/{{ $quotePlan->id }}" data-toggle="modal" data-target="#quotePlanModal" class="quotePlanModalPopup">View</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <h4>{{ $listQuotePlans }}</h4>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
@endif
@endsection
