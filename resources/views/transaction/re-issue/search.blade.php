@extends('layouts.app')
@section('title',$title.' Transaction')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>{{ $title == 'Home' ? 'View' : $title }} Transaction</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content" id="form-to-show">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
            <form method="get" action="{{ route($route) }}" id="search-transection" class="form-horizontal form-label-left" role="form" data-parsley-validate=""novalidate="" autocomplete="off">
                <div class="item form-group">
                    <label class="col-form-label col-md-2 col-sm-2" for="Approval Code">Approval Code<span class="required">*</span></label>
                    <div class="col-md-6 col-sm-6">
                        @if ($errors->has('approval_code'))
                            <span class="text-danger">{{ $errors->first('approval_code') }}</span>
                        @endif
                        <input class="form-control" name="approval_code" id="approval_code" value="{{ old('approval_code') }}" />
                        <small class="text-muted">Please enter Approval Code to search transaction</small><br/>
                    </div>
                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-2 col-sm-2" for="first-name"> </label>
                    <div class="col-md-6 col-sm-6">
                        <div class="input-group">
                            <button type="submit" class="btn btn-warning btn-sm">Search</button>
                        </div>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </div>
</div>
@endsection
