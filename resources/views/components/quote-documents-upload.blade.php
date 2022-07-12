@extends('layouts.app')
@section('title','Upload Documents')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Upload Documents</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('quotes/'.$quoteType.'/'.$quoteUuId.'') }}" class="btn btn-warning btn-sm">Go back</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <form method='post' action="{{ route('CarPlanManualProcess') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                    {{csrf_field()}}
                    <input type="hidden" id="quote_id" name="quote_id" value="{{ $quoteId }}">
                    <input type="hidden" id="quote_type_id" name="quote_type_id" value="{{ $quoteTypeId }}">

                   <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm">Upload Documents</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection