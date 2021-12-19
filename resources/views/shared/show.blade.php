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
                    <h2>{{(str_contains(strtolower($model->modelType), 'team') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType)). ' Detail' }}</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('quotes/'.strtolower($model->modelType)) }}" class="btn btn-warning btn-sm">{{(str_contains(strtolower($model->modelType), 'team') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType)). ' List' }}</a></li>
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
                    @php
                    $count = 1;
                    @endphp
                    @foreach($model->properties as $property => $value)
                        @if($count % 2 != 0)
                        <div class="item form-group">
                        @endif
                           <div class="col">
                                @if(strpos($value, 'title'))
                                    <label class="col-form-label col-md-6 col-sm-6" for="Status Description"><b>{{ strtoupper($customTitles[$property])}}</b></label>
                                @else
                                    <label class="col-form-label col-md-6 col-sm-6" for="Status Description"><b>{{str_replace("_"," ",strtoupper($property))}}</b></label>
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
                                            <p class="label-align-center">{{ $record->$propertyName}}</p>
                                        </div>
                                    @endif
                                @else
                                    @if(str_contains($value, 'customTable'))
                                    <div class="col-md-6 col-sm-6">
                                        <p class="label-align-center">{{ $customTableList[$property][0]->names }}</p>
                                    </div>
                                    @else
                                        <div class="col-md-6 col-sm-6">
                                            <p class="label-align-center">{{ $record->$property }}</p>
                                        </div>
                                    @endif
                                @endif
                           </div>
                        @if(count($model->properties) == $count && $count % 2 != 0)
                           <div class="col"></div>
                        </div>
                        @elseif($count % 2 == 0)
                            </div>
                        @endif
                        @php
                        $count++;
                        @endphp
                    @endforeach
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            @if (Auth::user()->hasRole('ADMIN') || Auth::user()->hasRole($model->modelType.'_MANAGER') || Auth::user()->hasRole($model->modelType.'_DEPUTY') ||Auth::user()->hasRole($model->modelType.'_ADVISOR'))
                            <a id="texta" href="{{ url('quotes/'.strtolower($model->modelType).'/'.$record->uuid.'/edit') }}" class='btn btn-warning btn-sm'>Edit</a>
                            @endif
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

                    </div>
                </div>
            </div>
        </div>

        <x-car-quote-more-detail
        :listQuoteVehicleDetails="$listQuoteVehicleDetails"
        :vehicleTypeText="$vehicleTypeText"
        />

        <x-car-ecom-detail
        :carQuotePremium="$record->premium"
        :carQuotePaidAt="$record->paid_at"
        :carQuotePaymentStatus="$record->payment_status_id_text"
        :carQuotePlanName="$record->plan_id_text"
        :carQuotePlanAddons="$carQuotePlanAddons"
        :carQuotePlanProvider="$record->car_plan_provider_id_text"
        />

        <x-car-quote-plans
            :listQuotePlans="$listQuotePlans"
            :uuid="$ecomCarInsuranceQuoteUrl.$record->uuid"
            :uuidModal="$record->uuid"
            :quoteRequestId="$record->id"
            :quoteIsCommerce="$record->is_ecommerce"
        />
    @endif
@endsection
