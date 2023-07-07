@extends('layouts.app')
@section('title','View Allocation Threshold')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
    $(function(){
        $('#update-allocation-btn').on('click', function(){
            var inputs = $('.form-group').find('input');
            var containErrors = false;
            for (let index = 0; index < inputs.length; index++) {
                const minPriceValue = parseFloat(inputs[index].value == '' ? 0 : inputs[index].value);
                const maxPriceValue = parseFloat(inputs[index + 1].value == '' ? 0 : inputs[index + 1].value);
                const minPrice = inputs[index];
                const maxPrice = inputs[index + 1];
                if(index == 0) {
                    if(minPriceValue < 1){
                        $('#' + minPrice.id + 'error').show().html('Minimum value cannot be zero').delay(2000).fadeOut();
                        containErrors = true;
                    }
                    if(maxPriceValue < 2 ){
                        $('#' + maxPrice.id + 'error').show().html('Max value cannot be 1').delay(2000).fadeOut();
                        containErrors = true;
                    }
                }
                if(index == 2 || index == 4) {
                    var lastMax = inputs[index - 1];
                    var lastMaxValue = parseFloat(inputs[index - 1].value  == '' ? 0 : inputs[index -1].value);
                    if(minPriceValue <= lastMaxValue || minPriceValue > (lastMaxValue + 1)){
                        $('#' + minPrice.id + 'error').show().html('Please correct min value').delay(2000).fadeOut();
                        containErrors = true;
                    }
                    if(maxPriceValue < 2 || maxPriceValue <= minPriceValue ){
                        $('#' + maxPrice.id + 'error').show().html('Please correct max value').delay(2000).fadeOut();
                        containErrors = true;
                    }
                }
                index++;
            }
            if(containErrors) return false;
            else{

                var teams = [];
                for (let index = 0; index < inputs.length; index++) {
                    const minPrice = inputs[index];
                    const maxPrice = inputs[index + 1];
                    const minPriceValue = parseFloat(inputs[index].value == '' ? 0 : inputs[index].value);
                    const maxPriceValue = parseFloat(inputs[index + 1].value == '' ? 0 : inputs[index + 1].value);

                    teams.push({
                        id: parseInt(minPrice.id.split('-')[0]),
                        min:minPriceValue,
                        max:maxPriceValue,
                    });
                    index++;
                }

                $.ajax({
                    url: "/update-team-allocation-threshold",
                    type: "post",
                    data: {
                        'teams': teams
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response) {
                            window.location.reload(1);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log(textStatus, errorThrown);
                    }
                });
            }
        });
    });
</script>
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Allocation Threshold</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><button id="update-allocation-btn" type="button"
                            href="{{ url('generic/allocation-threshold') }}" class="btn btn-success btn-m">Update</a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                @if(session()->has('message'))
                <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                @foreach ($teams as $team)
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-12">
                        <div class="col-md-4">
                            {{$team->name}}
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">

                                <div class="col-sm-4">
                                    <label>Min Price</label>
                                    <input type="number" class="form-control minprice" id={{$team->id . '-minprice'}}
                                    value="{{$team->min_price}}" />
                                    <label style="font: 15px;color:red;display:none;" id={{$team->id .
                                        '-minpriceerror'}}></label>
                                </div>
                                <div class="col-sm-4">
                                    <label>Max Price</label>
                                    <input type="number" class="form-control maxprice" id={{$team->id . '-maxprice'}}
                                    value="{{$team->max_price}}">
                                    <label style="font: 15px;color:red;display:none;" id={{$team->id .
                                        '-maxpriceerror'}}></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">

                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
