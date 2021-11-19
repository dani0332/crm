@extends('layouts.app')
@section('title', $leadType.' Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2> {{ucwords($leadType).' Detail'}}</h2>
                <ul class="nav navbar-right panel_toolbox">
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
                <form method='post' action="{{ route('myleads.update', $record[0]->id) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name"><b> First Name </b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $record[0]->first_name }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name"><b> Last Name </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record[0]->last_name }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email_address"><b> Email Address </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record[0]->email }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="phone_number"><b> Phone Number </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record[0]->mobile_no }}</p>
                        </div>
                    </div>
                </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <a id="texta" href="{{ route('myleads.edit', $record[0]->id) }}" class='btn btn-warning btn-sm'>Edit</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
