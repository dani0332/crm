@extends('layouts.app')
@section('title', 'Edit '.$model->modelType)
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<style>
    .form-control:disabled, .form-control[readonly]{
        background-color: white !important;
    }
</style>
<script>
    $(document).ready(function() {
        var model = JSON.parse('<?php echo json_encode(get_object_vars($model)) ?>');
        var modelPropertiesArray = convertObjectToArray(model.properties);
        var modelSkipProperties = convertObjectToArray(model.skipProperties);
        var result = modelSkipProperties.filter(obj => {
            return obj.name === 'update'
            });
        $('#skip').val(result[0].value);
        var record = JSON.parse('<?php echo json_encode($record) ?>');
        String.prototype.replaceAll = function(search, replacement) {
            var target = this;
            return target.replace(new RegExp(search, 'g'), replacement);
            };


            function convertObjectToArray(obj) {
            return Object.keys(obj).map(key => ({
                name: key,
                value: obj[key],
                }));
            }

        if(model.modelType == "Home") {
            $('#has_personal_belongings_div,#personal_belongings_aed_div,#has_building_div,#building_aed_div,#contents_aed_div').each(function(){
                if($(this).find('input').length > 0 && $(this).find('input').val() == ''){
                    $(this).hide();
                }
                if($(this).find('input').attr('type') == 'checkbox' > 0 && $(this).find('input').val() == 'off'){
                    $(this).hide();
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
                debugger;
                this.checked ? $('#personal_belongings_aed_div').show() : $('#personal_belongings_aed_div').hide();
            });
            $('#has_building').on('change',function(){
                debugger;
                this.checked ? $('#building_aed_div').show() : $('#building_aed_div').hide();
            });
            $('#has_contents').on('change',function(){
                debugger;
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
</script>
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>{{'Edit '.(str_contains(strtolower($model->modelType), 'team') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType))}}</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('quotes/'.strtolower($model->modelType)) }}" class="btn btn-warning btn-sm">{{(str_contains(strtolower($model->modelType), 'team') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType)).' List'}}</a></li>
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
                    <form id="demo-form2" method='post'
                        action="{{ route(strtolower($model->modelType).'.update', $record->uuid) }}"
                        enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left"
                        autocomplete="off">
                        {{ csrf_field() }}
                        @method('PUT')

                        <input type="hidden" name="model" value={{ json_encode($model->properties) }} />
                        <input type="hidden" name="modelType" value={{ json_encode($model->modelType) }} />
                        <input type="hidden" name="modelSkipProperties" id="skip" value={{ json_encode($model->modelSkipProperties) }} />
                        @php
                        $index = 0;
                        @endphp
                        @foreach($model->properties as $property => $value)
                            @if(!str_contains($model->skipProperties['update'], $property))
                                @if($index == 0 || strpos($value, 'checkbox'))
                                @else
                                    <div @if(count($model->properties) <6) class="col-md-12" @else class="col-md-6" @endif id={{$property.'_div'}}>
                                        @if(strpos($value, 'input') !== false )
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
                                            <input
                                                @if(explode("|", $value)[1] == "date") readonly="readonly" @endif
                                                @if(explode("|", $value)[1] != 'date')
                                                    type={{ explode("|", $value)[1]  }}
                                                    @endif id={{$property}}
                                                name={{$property}}
                                                @if($property == 'email' || $property == 'mobile_no') disabled="disabled" @endif
                                                value="{{ old($property, $record->$property) }}"
                                            class="form-control">
                                            @if ($errors->has($property))
                                                <span class="text-danger">{{ $errors->first($property) }}</span>
                                            @endif
                                        @endif
                                        @if(strpos($value, 'select') !== false)
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
                                            <select @if(strpos($value, 'multiple')) multiple="multiple" name="{{$property.'[]'}}" class="form-control select2 select-roles" @else name="{{$property}}" class="form-control" @endif id="{{$property}}" >
                                                @if(strpos($value, 'title'))
                                                    <option value="">{{"Please select ".$customTitles[$property] }}</option>
                                                @else
                                                    <option value="">{{"Please select ".str_replace("id"," ",str_replace("_"," ",$property)) }}</option>
                                                @endif
                                                @if (strpos($value, 'customTable') !== false)
                                                    @foreach($customLists[$property] as $selectedItem)
                                                        @foreach($dropdownSource[$property] as $item)
                                                            @if ($selectedItem->id == $item->id)
                                                                <option value="{{$item->id}}" selected="selected">
                                                                {{ $item->text ?? $item->name }}
                                                                </option>
                                                            @else
                                                                <option value="{{$item->id}}">
                                                                {{ $item->text ?? $item->name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    @endforeach
                                                @else
                                                @foreach($dropdownSource[$property] as $item)
                                                    <option value="{{$item->id}}" {{ $item->id == old($item->id, $record->$property) ? 'selected' : ''}}>{{ $item->text ?? $item->name }}</option>
                                                @endforeach

                                                @endif

                                            </select>
                                            @if ($errors->has($property))
                                            <span class="text-danger">{{ $errors->first($property) }}</span>
                                            @endif
                                        @endif
                                        @if(strpos($value, 'textarea') !== false )
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
                                            <textarea
                                                id={{$property}}
                                                name={{$property}}
                                            class="form-control">{{ old($property, $record->$property) }}</textarea>
                                            @if ($errors->has($property))
                                                <span class="text-danger">{{ $errors->first($property) }}</span>
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            @endif
                            @php
                            $index++
                            @endphp

                        @endforeach

                        @foreach ($model->properties as $property => $value)
                            @if(!str_contains($model->skipProperties['update'], $property))
                                @if (strpos($value, 'checkbox'))
                                    <div class="col-md-2" id={{$property.'_div'}}>
                                        <div class="col-md-9">
                                            <label for="middle-name" style="margin-top: 8px;">
                                                <b>
                                                    @if(strpos($value, 'title'))
                                                        {{ strtoupper($customTitles[$property])}}
                                                    @else
                                                        {{str_replace("_"," ",strtoupper($property))}}
                                                    @endif
                                                </b>
                                            </label>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="checkbox" {{ $record->$property ? 'checked' : '' }} style="float: right;" id={{$property}} name={{$property}}>                                        </div>
                                    </div>
                                    <br />
                                    @if ($errors->has($property))
                                        <span class="text-danger">{{ $errors->first($property) }}</span>
                                    @endif
                                @endif
                            @endif
                        @endforeach
                        <div style="clear: both;"></div>
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
