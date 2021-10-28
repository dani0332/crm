@extends('layouts.app')
@section('title', 'Edit '.$model->modelType)
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>

<script>
    $(document).ready(function() {
    var model = JSON.parse('<?php echo json_encode(get_object_vars($model)) ?>');
    var record = JSON.parse('<?php echo json_encode($record) ?>');
    Object.keys(model.properties).forEach(element => {
        if(model.properties[element].indexOf('date') > -1){
            $("#"+ element).val($("#"+ element).val().split(' ')[0]);
        }
    });
});
</script>
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>{{'Edit '.$model->modelType}}</h2>
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
                    <form id="demo-form2" method='post'
                        action="{{ route(strtolower($model->modelType).'.update', $record) }}"
                        enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left"
                        autocomplete="off">
                        {{ csrf_field() }}
                        @method('PUT')

                        <input type="hidden" name="model" value={{ json_encode($model->properties) }} />
                        <input type="hidden" name="modelType" value={{ json_encode($model->modelType) }} />
                        @php
                        $index = 0
                        @endphp
                        @foreach($model->properties as $property => $value)
                            @if($index == 0 || strpos($value, 'checkbox'))
                            @else
                                <div @if(count($model->properties) <6) class="col-md-12" @else class="col-md-6" @endif>
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
                                            @if(explode("|", $value)[1] != 'date')
                                                type={{ explode("|", $value)[1]  }}
                                                @endif id={{$property}}
                                            name={{$property}}
                                            value="{{ old($property, $record[$property]) }}"
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
                                            <option value="">{{"Please select ".str_replace("_"," ",$property) }}</option>
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
                                                <option value="{{$item->id}}" {{ $item->id == old($item->id, $record[$property]) ? 'selected' : ''}}>{{ $item->text ?? $item->name }}</option>
                                            @endforeach

                                            @endif

                                        </select>
                                        @if ($errors->has($property))
                                        <span class="text-danger">{{ $errors->first($property) }}</span>
                                        @endif
                                    @endif
                                </div>
                            @endif
                            @php
                            $index++
                            @endphp
                        @endforeach

                        @foreach ($model->properties as $property => $value)
                            @if (strpos($value, 'checkbox'))

                                <div class="col-md-2">
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
                                        <input type="checkbox" {{ $record[$property] ? 'checked' : '' }} style="float: right;" id="name" name={{$property}}>                                        </div>
                                </div>
                                <br />
                                @if ($errors->has($property))
                                    <span class="text-danger">{{ $errors->first($property) }}</span>
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
