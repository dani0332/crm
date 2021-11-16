@extends('layouts.app')
@section('title','Upload UAE Sanction List')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Upload UAE Sanction List</h2>
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
                <form id="demo-form2" method='post' action="{{ url('/kyc/aml/upload/uae-list') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="File">File <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="file" id="file_name" name="file_name" class="form-control form-control-sm" accept='.csv,.xlsx' data-toggle="tooltip" data-placement="top" title="Please select .xlsx file to upload" />
                            @if ($errors->has('file_name'))
                                <span class="text-danger">{{ $errors->first('file_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">

                    </div>
                    <div class="item form-group">

                    </div>
                    <div class="item form-group">
                        <p><h4>Required file format</h4></p>
                        <p><ul>
                            <li>File must be a xls file with the following fields.</li>
                            <li>Please ensure max allowed size is 2mb (2048kb)</li>
                            <li>File name should always be => <strong>UAESanctionlist.xls</strong></li>
                        </ul></p>
                    </div>
                    <div class="item form-group">

                    </div>
                    <div class="item form-group">

                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                        <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Upload</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
