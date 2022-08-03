@extends('layouts.app')
@section('title', 'Add '.$model->modelType )
@section('content')
<style>
    .form-control:disabled, .form-control[readonly]{
        background-color: white !important;
    }
</style>
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Create {{$model->modelType}}</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url()->previous() }}" class="btn btn-warning btn-sm">Back to Previous</a></li>
                        <li><a href="{{ url('generic/'.strtolower($model->modelType)) }}" class="btn btn-warning btn-sm">{{$model->modelType}} List</a></li>
                        
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
                    <form id="demo-form2" autocomplete="off" action="{{ route('save') }}" method='post' enctype="multipart/form-data"
                        data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                        {{ csrf_field() }}
                        <input type="hidden" name="model" value={{ json_encode($model->properties) }} />
                        <input type="hidden" name="modelSkipProperties" value={{ json_encode($model->skipProperties) }} />
                        <input type="hidden" name="modelType" value={{ json_encode($model->modelType) }} />
                        <input type="hidden" name="addon_id" @if(isset($id)) value={{$id}} @endif />
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
                                        <input @if(explode("|", $value)[1] == "date") readonly="readonly" @endif  
                                        @if(strpos($value, 'min') !== false)
                                            
                                            min="{{explode(":", $value)[1]}}"
                                        @endif
                                        type={{ explode("|", $value)[1] }} id={{$property}} name={{$property}} value="{{ old($property) }}" class="form-control">
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
                                        
                                        <select @if(strpos($value, 'multiple')) name="{{$property.'[]'}}" multiple="multiple" class="form-control select2 select-roles" @else class="form-control" name="{{$property}}" @endif id="{{$property}}">
                                            @if(strpos($value, 'title'))
                                                <option value="">{{"Please select ".$customTitles[$property] }}</option>
                                            @else
                                                <option value="">{{"Please select ".str_replace("id"," ",str_replace("_"," ",$property)) }}</option>
                                            @endif
                                            @foreach($dropdownSource[$property] as $item)
                                            
                                                <option value="{{ $item->id }}" @if(old($property) == $item->id) selected @endif @if($id == $item->id) selected @endif>{{ $item->text ?? $item->name }}</option>
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
                                            @if($item == \App\Enums\GenericRequestEnum::Selectstring || $item == \App\Enums\GenericRequestEnum::CheckboxString)
                                            @php $item = strtolower($item); @endphp
                                            @endif
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
                                            <input type={{ explode("|", $value)[1]  }} {{ old($property) ? 'checked' : '' }} style="float: right;" id={{$property}} name={{$property}} >
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
                                <button type="submit" class="btn btn-warning btn-sm">Create </button>
                                <button type="submit" name="return_to_view" value="1" class="btn btn-warning btn-sm">Create & Add New</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
