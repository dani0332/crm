@extends('layouts.app')
@section('title','Edit Team')
@section('content')
@inject('teamService', 'App\Services\TeamService')
@php
use App\Enums\TeamTypeEnum;
use App\Enums\GenericRequestEnum;
@endphp
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
    function renderParentOptions(products)
    {
        var selectedType = $('#type option:selected').val();
        $('#parent_team_id').empty();
        debugger;
        if( selectedType == 1) {
            $('#parent_team_id').prop('disabled', true);
        }else{
            $('#parent_team_id').prop('disabled', false);
            for (let index = 0; index < products.length; index++) {
            const element = products[index];
                if(element.type == '{{TeamTypeEnum::PRODUCT_STR}}' && selectedType == '{{TeamTypeEnum::TEAM}}') {
                    $('#parent_team_id').append('<option value="'+ element.id +'" >'+ element.name +'</option>');
                }
                if(element.type == '{{TeamTypeEnum::TEAM_STR}}' && selectedType == '{{TeamTypeEnum::SUB_TEAM}}') {
                    $('#parent_team_id').append('<option value="'+ element.id +'" >'+ element.name +'</option>');
                }
            }
        }
    }

    $(document).ready(function(){
        var products  = JSON.parse('<?php echo json_encode($products); ?>');
        $('#type').on('change', function (item, index){ 
            renderParentOptions(products);
        });

        var $allocationPriceSection = $('.allocation-price-section');
        if($('#allocation_threshold_enabled').is(':checked')) {
            $('.allocation-price-section').show();
        }
        $('#allocation_threshold_enabled').on('change', function() {
            $allocationPriceSection.toggle(this.checked);
        });
    });


</script>
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Team</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('team.index') }}" id="" class="btn btn-warning">Teams List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('team.update', ['team' => $team->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                @method('PUT')
                <div class="item form-group">
                    <div class="col">
                        <span class="col-form-label col-md-6 col-sm-6">Name <span class="required">*</span></span>
                        <input type="text" id="name" name="name" value="{{ old('name', $team->name) }}" class="form-control">
                        @if ($errors->has('name'))
                            <span class="text-danger">{{ $errors->first('name') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <span class="col-form-label col-md-6 col-sm-6">Record Type <span class="required">*</span></span>

                        <select class="form-control" id='type' name='type'>
                        <option @if($team->type == 'Product') selected @endif  value='1'>Product</option>
                        <option @if($team->type == 'Team') selected @endif value='2'>Team</option>
                        <option @if($team->type == 'Subteam') selected @endif value='3'>Subteam</option>
                        </select>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <span class="col-form-label col-md-6 col-sm-6">Parent</span>
                        <select class="form-control" id='parent_team_id' name='parent_team_id'>
                            @foreach ($products as $product)
                                @if($team->type == 'Team' && $product->type == 'Product')
                                <option @if($teamService->getTeamNameById($team->parent_team_id) == $product->name) selected="selected" @endif value="{{$product->id}}"> {{$product->name}} </option>
                                @endif

                                @if($team->type == 'Subteam' && $product->type == 'Team')
                                <option @if($teamService->getTeamNameById($team->parent_team_id) == $product->name) selected="selected" @endif value="{{$product->id}}"> {{$product->name}} </option>
                                @endif
                            @endforeach
                        </select>
                        @if ($errors->has('parent_team_id'))
                            <span class="text-danger">{{ $errors->first('parent_team_id') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <span class="col-form-label col-md-6 col-sm-6">Is Active
                        <br>
                        @if ($team->is_active === GenericRequestEnum::TRUE)
                            <input type="checkbox" name="is_active" checked="">
                        @else
                            <input type="checkbox" name="is_active"  {{ old("is_active") ? "checked" : "" }} >
                        @endif
                        </span>

                        <span class="col-form-label col-md-6 col-sm-6">Enable Lead Allocation Threshold
                            <br />
                            <input type="checkbox" id="allocation_threshold_enabled" name="allocation_threshold_enabled"  {{ old("allocation_threshold_enabled", $team->allocation_threshold_enabled) ? "checked" : "" }} >
                        </span>
                    </div>
                </div>
                <div class="item form-group allocation-price-section" @if(!$team->allocation_threshold_enabled) style="display: none;" @endif>
                    <div class="col">
                        <span class="col-form-label col-md-6 col-sm-6">Min Price </span>
                        <input type="text" id="min_price" name="min_price" value="{{ old('min_price', $team->min_price) }}" class="form-control">
                        @if ($errors->has('min_price'))
                            <span class="text-danger">{{ $errors->first('min_price') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <span class="col-form-label col-md-6 col-sm-6">Max Price </span>
                        <input type="text" id="max_price" name="max_price" value="{{ old('max_price', $team->max_price) }}" class="form-control">
                        @if ($errors->has('max_price'))
                            <span class="text-danger">{{ $errors->first('max_price') }}</span>
                        @endif
                    </div>
                </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                           <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
