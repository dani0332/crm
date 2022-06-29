@extends('layouts.app')
@section('title', 'Edit '.$model->modelType)
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<style>
    .form-control:disabled, .form-control[readonly]{
        background-color: white !important;
    }
</style>
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Edit {{$model->modelType}}</h2>
                    <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url()->previous() }}" class="btn btn-warning btn-sm">Back to Previous</a></li>
                        <li><a href="{{ url('generic/'.strtolower($model->modelType)) }}" class="btn btn-warning btn-sm">{{(str_contains(strtolower($model->modelType), 'team') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType)).' List'}}</a></li>
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
                        action="{{ route(strtolower($model->modelType).'.update', $record->id) }}"
                        enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left"
                        autocomplete="off">
                        {{ csrf_field() }}
                        @method('PUT')

                        <input type="hidden" name="model" value={{ json_encode($model->properties) }} />
                        <input type="hidden" name="modelType" value={{ json_encode($model->modelType) }} />
                        <input type="hidden" name="modelSkipProperties" id="skip" value={{ json_encode($model->modelSkipProperties) }} />
                        @php
                        $index = 0
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
                                                    @if(strpos($value, 'min') !== false)
                                                        min="{{explode(":", $value)[1]}}"
                                                    @endif
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
                                                @if(gettype($record->$property) != 'string')
                                                    <option value="{{$item->id}}" {{ $item->id == old($item->id, $record->$property) ? 'selected' : ''}}>{{ $item->text ?? $item->name }}</option>
                                                @else
                                                <option value="{{$item->id}}" {{ $item->text == old($item->text, $record->$property) ? 'selected' : ''}}>{{ $item->text ?? $item->name }}</option>
                                                @endif
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
                                        @if(strpos($value, 'static') !== false )
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
                                        @if ($errors->has($property))
                                        <span class="text-danger">{{ $errors->first($property) }}</span>
                                        @endif
                                        <select @if(strpos($value, 'multiple')) name="{{$property.'[]'}}" multiple="multiple" class="form-control select2 select-roles" @else class="form-control" name="{{$property}}" @endif id="{{$property}}" >
                                            @foreach($staticOptions as $item)
                                            <option value="{{ $item }}" {{ $item == old($item, $record->$property) ? 'selected' : ''}}>{{ $item }}</option>
                                            @endforeach
                                        </select>
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
                                            <input type="checkbox" {{ $record->$property ? 'checked' : '' }} style="float: right;" id={{$property}} name={{$property}} value={{$record->$property}}>                                        </div>
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
