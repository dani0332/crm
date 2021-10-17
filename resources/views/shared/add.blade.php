@extends('layouts.app')
@section('title', 'Add '.$quote->quoteType )
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Create {{ $quote->quoteType }}</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a class="btn btn-warning btn-sm">{{ $quote->quoteType }} List</a></li>
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
                        <input type="hidden" name="model" value={{ json_encode($quote->properties) }} />
                        <input type="hidden" name="quoteType" value={{ json_encode($quote->quoteType) }} />
                        <div class="item form-group">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Name <span
                                    class="required">*</span></label>
                            <div class="col-md-6 col-sm-6">
                                <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control">
                                @if ($errors->has('name'))
                                    <span class="text-danger">{{ $errors->first('name') }}</span>
                                @endif
                            </div>
                        </div>

                        @foreach($quote->properties as $property => $value)
                            <div class="item form-group">
                                <div class="col-md-6 col-sm-6">
                               @if(strpos($value, 'input') !== false)
                                 <label class="col-form-label col-md-6 col-sm-6 label-align" for="name">{{$property}} @if(strpos($value, "required") == true) <span class='required'>*</span>@endif</label>
                                 <input type={{ explode("|", $value)[1]  }} id="name" name={{$property}} value="{{ old($property) }}" class="form-control">
                               @endif
                               </div>
                            </div>
                        @endforeach


                        <div id='redirect_to_view_div'></div>
                        <div class="ln_solid"></div>
                        <div class="row">
                            <div class="col-auto mr-auto"></div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-warning btn-sm">Create & Add New</button> <button
                                    type="submit" class="btn btn-warning btn-sm" id="return_to_view">Create</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
