@extends('layouts.app')
@section('title', $model->modelType.' Detail')
@section('content')
<style>
#quote-plans table.dataTable thead .sorting_asc:after {
    content: none !important;
}
</style>
<?php
    use App\Enums\InsuranceProvder;
?>
    <div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel">
                <br />
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                <div class="alert alert-success" style="display: none" id="teamassignmentSuccess"></div>
                <div class="x_title">
                    <h2>{{ $model->modelType. ' Detail' }}</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('generic/'.strtolower($model->modelType)) }}" class="btn btn-warning btn-sm">{{(str_contains(strtolower($model->modelType), 'team') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType)). ' List' }}</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>      
                <div class="x_content">

                    @php
                    $count = 1;
                    @endphp
                    @foreach($model->properties as $property => $value)
                        @if(!str_contains($model->skipProperties['show'], $property))
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
                                        <div class="col-md-6 col-sm-6" style="text-overflow: ellipsis;overflow: auto;white-space: nowrap;width: 495px;">
                                            <p class="label-align-center">{{ $customTableList[$property][0]->names }}</p>
                                        </div>
                                        @else
                                            <div class="col-md-6 col-sm-6">
                                                <p class="label-align-center">{{ $record->$property}}</p>
                                            </div>
                                        @endif
                                    @else
                                        @if(str_contains($value, 'customTable'))
                                        <div class="col-md-6 col-sm-6" style="text-overflow: ellipsis;overflow: auto;white-space: nowrap;width: 495px;">
                                            <p class="label-align-center">{{ $customTableList[$property][0]->names }}</p>
                                        </div>
                                        @else
                                            <div class="col-md-6 col-sm-6" style="text-overflow: ellipsis;overflow: auto;white-space: nowrap;width: 495px;">
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
                        @endif
                    @endforeach
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <a id="texta" href="{{ url('generic/'.strtolower($model->modelType).'/'.$record->id.'/edit') }}" class='btn btn-warning btn-sm'>Edit</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if($model->modelType == InsuranceProvder::Name)
        <x-provider-car-plans 
        :plansList="$plansList" 
        :uuidModal="$record->id"
        :RequestId="$record->id" />
    @endif
    @if($model->modelType == InsuranceProvder::PlanName)
        <x-provider-car-plans-coverage 
        :coverageList="$coverageList" 
        :uuidModal="$record->id"
        :RequestId="$record->id" />
    @endif
    @if($model->modelType == InsuranceProvder::Coverage)
        <x-car-plan-addon
        :plansList="$plansList" 
        :uuidModal="$record->id"
        :RequestId="$record->id" />
    @endif
@endsection
