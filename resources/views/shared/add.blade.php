@extends('layouts.app')
@section('title', 'Add '.$model->modelType )
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
    $(document).ready(function(){
        String.prototype.replaceAll = function(search, replacement) {
        var target = this;
        return target.replace(new RegExp(search, 'g'), replacement);
        };

        var oldCarModelId = JSON.parse('<?php echo json_encode(old("car_model_id")) ?>');

        if(oldCarModelId != '') {
            getCarModels(oldCarModelId);
        }

        $('#car_model_id').on('change',function(){
            var car_model_id = $('#car_model_id').val();
            $.ajax({
                url: "{{ url('/getCarModelDetails') }}",
                type: "GET",
                data: {
                    car_model_id: car_model_id
                },
                success: function(data){
                        console.log("cylinder: ",data.cylinder);
                        console.log("seat_capacity: ",data.seat_capacity);
                        console.log("vehicle_type_id: ",data.vehicle_type_id);
                    if(data.cylinder || data.seat_capacity || data.vehicle_type_id){
                        $('#cylinder').val(data.cylinder);
                        $('#seat_capacity').val(data.seat_capacity);
                        if(data.vehicle_type_id) $('#vehicle_type_id').val(data.vehicle_type_id);
                    }
                    if(!data.cylinder && !data.seat_capacity && !data.vehicle_type_id){
                        alert('No Vehicle Assumptions Data Found');
                    }
                },
                error: function(data){
                    console.log(data);
                }
            });
        });
        function convertObjectToArray(obj) {
        return Object.keys(obj).map(key => ({
            name: key,
            value: obj[key],
            }));
        }
        var model = JSON.parse('<?php echo json_encode(get_object_vars($model)) ?>');
        var modelPropertiesArray = convertObjectToArray(model.properties);
        if(model.modelType == "Home") {
            $('#has_personal_belongings_div,#personal_belongings_aed_div,#has_building_div,#building_aed_div,#contents_aed_div').each(function(){
                debugger;
                if($('#'+$(this).attr('id').replace('_div', '')).attr('type') == 'checkbox'){
                    if(!$('#'+$(this).attr('id').replace('_div', '')).is(':checked')) {
                        $(this).hide();
                    }
                }
                else{
                    if($('#'+$(this).attr('id').replace('_div', '')).val() == '') {
                        $(this).hide();
                    }
                }
            });
            $('#iam_possesion_type_id').on('change',function(){
                debugger;
                if($("#iam_possesion_type_id option:selected").text() == 'A landlord'){
                    $('#has_building_div').show();
                }
                else{
                    $('#has_building_div').hide();
                }
            });
            $('#has_personal_belongings').on('change',function(){
                this.checked ? $('#personal_belongings_aed_div').show() : $('#personal_belongings_aed_div').hide();
            });
            $('#has_building').on('change',function(){
                this.checked ? $('#building_aed_div').show() : $('#building_aed_div').hide();
            });
            $('#has_contents').on('change',function(){
                if(this.checked){
                    if($("#iam_possesion_type_id option:selected").text() == 'A landlord'){
                        $('#has_building_div').show();
                    }
                    $('#contents_aed_div').show();
                    $('#has_personal_belongings_div').show();
                }
                else{
                    $('#contents_aed_div').hide();
                    $('#has_personal_belongings_div').hide();
                }
            });
        }
    });

    function getCarModels(id)
    {
        $.get('/car-model-by-id?id=' + id, function (data) {
            var carmodel = $('#car_model_id').empty();
            $.each(data, function (create, carmodelObj) {
                var option = $('<option/>', { id: create, value: carmodelObj });
                carmodel.append('<option data-id="' + carmodelObj.code + '" value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
            });
        });
    }

</script>
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Create {{str_contains(strtolower($model->modelType), 'team') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType)}}</h2>
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
                    <form id="demo-form2" autocomplete="off" action="{{ route('saveQuote') }}" method='post' enctype="multipart/form-data"
                        data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                        {{ csrf_field() }}
                        <input type="hidden" name="model" value={{ json_encode($model->properties) }} />
                        <input type="hidden" name="modelSkipProperties" value={{ json_encode($model->skipProperties) }} />
                        <input type="hidden" name="modelType" value={{ json_encode($model->modelType) }} />

                        @php
                        $index = 0
                        @endphp

                        @foreach($model->properties as $property => $value)
                            @if(strpos($value, 'checkbox'))
                            @else
                                @if(!str_contains($model->skipProperties['create'], $property))
                                    @if(strpos($value, 'input') !== false )
                                    <div @if(count($model->properties) < 6) class="col-md-12" @else class="col-md-6" @endif id={{$property.'_div'}}>
                                    <div class="col">
                                        <span class="col-form-label col-md-6 col-sm-6" for="name">
                                            @if(strpos($value, 'title'))
                                                {{ strtoupper($customTitles[$property])}}
                                            @else
                                                {{str_replace("_"," ",strtoupper($property))}}
                                            @endif
                                            @if(strpos($value, "required") == true)
                                            <span class='required'>*</span>
                                            @endif
                                        </span>
                                        <input type={{ explode("|", $value)[1] }} id={{$property}} name={{$property}} value="{{ old($property) }}" class="form-control">
                                        @if ($errors->has($property))
                                            <span class="text-danger">{{ $errors->first($property) }}</span>
                                        @endif
                                    </div>
                                    </div>
                                    @endif
                                    @if(strpos($value, 'select') !== false)
                                    <div @if(count($model->properties) <6) class="col-md-12" @else class="col-md-6" @endif id={{$property.'_div'}}>
                                    <div class="col">
                                        <span class="col-form-label col-md-6 col-sm-6" for="name">
                                            @if(strpos($value, 'title'))
                                                {{ strtoupper($customTitles[$property]) }}
                                            @else
                                                {{str_replace("_"," ",strtoupper($property))}}
                                            @endif
                                            @if(strpos($value, "required") == true)
                                            <span class='required'>*</span>
                                            @endif
                                        </span>
                                        <select @if(strpos($value, 'multiple')) name="{{$property.'[]'}}" multiple="multiple" class="form-control select2 select-roles" @else class="form-control" name="{{$property}}" @endif id="{{$property}}" >
                                            @if(strpos($value, 'title'))
                                                <option value="">{{"Please select ".$customTitles[$property] }}</option>
                                            @else
                                                <option value="">{{"Please select ".str_replace("id"," ",str_replace("_"," ",$property)) }}</option>
                                            @endif
                                            @foreach($dropdownSource[$property] as $item)

                                                <option value="{{ $item->id }}"  @if(old($property) == $item->id) selected @endif>{{ $item->text ?? $item->name }}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has($property))
                                        <span class="text-danger">{{ $errors->first($property) }}</span>
                                        @endif
                                    </div>
                                    </div>
                                    @endif
                                    @if(strpos($value, 'textarea') !== false)
                                    <div @if(count($model->properties) <6) class="col-md-12" @else class="col-md-6" @endif>
                                    <div class="col">
                                        <span class="col-form-label col-md-6 col-sm-6" for="name">
                                            @if(strpos($value, 'title'))
                                                {{ strtoupper($customTitles[$property]) }}
                                            @else
                                                {{str_replace("_"," ",strtoupper($property))}}
                                            @endif
                                            @if(strpos($value, "required") == true)
                                            <span class='required'>*</span>
                                            @endif
                                        </span>
                                        <textarea id={{$property}} name={{$property}} value="{{ old($property) }}" class="form-control"></textarea>
                                        @if ($errors->has($property))
                                        <span class="text-danger">{{ $errors->first($property) }}</span>
                                        @endif
                                    </div>
                                    </div>
                                    @endif
                                    @if(strpos($value, 'static') !== false )
                                    <div @if(count($model->properties) < 6) class="col-md-12" @else class="col-md-6" @endif id={{$property.'_div'}}>
                                    <div class="col">
                                        <span class="col-form-label col-md-6 col-sm-6" for="name">
                                            @if(strpos($value, 'title'))
                                                {{ strtoupper($customTitles[$property])}}
                                            @else
                                                {{str_replace("_"," ",strtoupper($property))}}
                                            @endif
                                            @if(strpos($value, "required") == true)
                                            <span class='required'>*</span>
                                            @endif
                                        </span>
                                        @php
                                            $propertyLastIndex = explode('|', $model->properties[$property]);
                                            $staticOptionString = end($propertyLastIndex);
                                            $staticOptions = explode(',', $staticOptionString);
                                        @endphp

                                        <select @if(strpos($value, 'multiple')) name="{{$property.'[]'}}" multiple="multiple" class="form-control select2 select-roles" @else class="form-control" name="{{$property}}" @endif id="{{$property}}" >
                                            @foreach($staticOptions as $item)
                                            <option value="{{ $item }}">{{ $item }}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has($property))
                                        <span class="text-danger">{{ $errors->first($property) }}</span>
                                        @endif
                                    </div>
                                    </div>
                                    @endif
                                @endif
                            @endif
                            @php
                            $index++
                            @endphp
                        @endforeach
                        @if(count($model->skipProperties) != 0)
                            </div>
                        @endif

                            @foreach ($model->properties as $property => $value)
                                @if (strpos($value, 'checkbox'))

                                    <div class="col-md-3" id={{$property.'_div'}}>
                                        <div class="col-md-8">
                                            <label for="middle-name" style="margin-top: 8px;float: left">
                                                <b>
                                                    @if(strpos($value, 'title'))
                                                        {{ strtoupper($customTitles[$property])}}
                                                    @else
                                                        {{str_replace("_"," ",strtoupper($property))}}
                                                    @endif
                                                </b>

                                            </label>
                                            @if(strpos($value, "required") == true)
                                            <span class='required' style="float: left;margin-top: 8px;margin-left: 1px;">*</span>
                                            @endif
                                            @if ($errors->has($property))
                                                <span style="float: left" class="text-danger">{{ $errors->first($property) }}</span>
                                            @endif
                                        </div>
                                        <div class="col-md-2">
                                            <input type={{ explode("|", $value)[1]  }} {{ old($property) ? 'checked' : '' }} style="float: right;" id={{$property}} name={{$property}}>
                                        </div>
                                    </div>
                                    <br />

                                @endif
                            @endforeach
                        <div style="clear: both;"></div>
                        <div id='redirect_to_view_div'></div>
                        <div class="ln_solid"></div>
                        <div class="row">
                            <div class="col-auto mr-auto"></div>
                            <div class="col-auto">
                                <button
                                    type="submit" class="btn btn-warning btn-sm">Create</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
