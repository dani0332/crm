@extends('layouts.app')
@section('title', 'Add '.$model->modelType )
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Create {{ $model->modelType }}</h2>
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
                    <form id="demo-form2" action="{{ route('saveQuote') }}" method='post' enctype="multipart/form-data"
                        data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                        {{ csrf_field() }}
                        <input type="hidden" name="model" value={{ json_encode($model->properties) }} />
                        <input type="hidden" name="modelType" value={{ json_encode($model->modelType) }} />

                        @php
                        $index = 0
                        @endphp

                        @foreach($model->properties as $property => $value)
                            @if($index == 0 || strpos($value, 'checkbox'))
                            @else
                                <div class="col-md-6">
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
                                        <input type={{ explode("|", $value)[1]  }} id="name" name={{$property}} value="{{ old($property) }}" class="form-control">
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
                                        <select class="form-control" id="{{$property}}" name="{{$property}}">
                                            <option value="">{{"Please select ".str_replace("_"," ",$property) }}</option>
                                            @foreach($dropdownSource[$property] as $item)
                                                @if (old($property) == $item->id)
                                                <option value="{{ $item->id }}" selected>{{ $item->text }}</option>
                                                @else
                                                <option value="{{ $item->id }}">{{ $item->text }}</option>
                                                @endif
                                            @endforeach
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
                        @if(count($model->skipProperties) != 0)
                            </div>
                        @endif

                            @foreach ($model->properties as $property => $value)
                                @if (strpos($value, 'checkbox'))

                                    <div class="col-md-2">
                                        <div class="col-md-6">
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
                                            <input type={{ explode("|", $value)[1]  }} {{ old($property) ? 'checked' : '' }} style="float: right;" id="name" name={{$property}}>
                                        </div>
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
                                <button
                                    type="submit" class="btn btn-warning btn-sm" id="return_to_view">Create</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

