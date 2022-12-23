@extends('layouts.app')
@section('title','Upload Customers')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Upload Customers</h2>
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
                <form id="demo-form2" method='post' action="{{ url('customer-process') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                    {{csrf_field()}}
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">File <span class="required">*</span></span>
                            <input type="file" id="file_name" name="file_name" class="form-control form-control-sm" accept='.xlsx' data-toggle="tooltip" data-placement="top" title="Please select .xlsx file to upload" />
                            @if ($errors->has('file_name'))
                                <span class="text-danger">{{ $errors->first('file_name') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">CDB ID <span class="required">*</span></span>
                            <input type="text" id="cdb_id" name="cdb_id" value="{{ old('cdb_id') }}" class="form-control form-control-sm" placeholder="CDB ID" />
                            @if ($errors->has('cdb_id'))
                                <span class="text-danger">{{ $errors->first('cdb_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Policy Expiry Date <span class="required">*</span></span>
                            <input type="date" id="myalfred_expiry_date"  value="{{ old('myalfred_expiry_date') }}"  name="myalfred_expiry_date" class="form-control form-control-sm"  />
                            @if ($errors->has('myalfred_expiry_date'))
                                <span class="text-danger">{{ $errors->first('myalfred_expiry_date') }}</span>
                            @endif
                        </div>
                        <div class="col">
                        <br />
                            <span class="col-form-label col-md-6 col-sm-6">Send Invitation Email <span class="required">*</span></span>
                            <input type="checkbox" id="inviatation_email" checked name="inviatation_email" class="flat"  />
                            @if ($errors->has('inviatation_email'))
                                <span class="text-danger">{{ $errors->first('inviatation_email') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">

                    </div>
                    <div class="item form-group">

                    </div>
                    <div class="item form-group">
                        <p><h4 class="required"><b>Import Instructions must be follow:</b></h4></p>
                        <p><ul class="required" style="font-weight:bold;">
                            <li>File must be a xlsx file with the following fields.</li>
                            <li>Please ensure there are no commas in file.</li>
                            <li>First line will be skipped while uploading.</li>
                            <li>Please ensure there are no spaces in start and end of columns data</li>
                            <li>Please ensure max allowed size is 2mb (2048kb)</li>
                            <li>Arabic is not supported in CSV file upload</li>
                        </ul></p>
                    </div>
                    <div class="item form-group">
                        <div class="col-md-3">
                            Download Sample XLSX <a href="https://myalfreddev.blob.core.windows.net/myrewards/81205DF5-E2A5-4626-D5DD-13656D4D9E2C_test-new.xlsx"><img src="https://img.icons8.com/color/40/000000/ms-excel.png" alt="xlsx" border="0" /></a>
                        </div>
                    </div>
                    <div class="item form-group">

                    </div>
                    <div class="item form-group">

                    </div>
                    <div class="item form-group">
                        <div class="col-md-12 scrollable">
                            <table class="table table-bordered">
                            <thead>
                                <tr><th>Sr No.</th>
                                <th>Field name</th>
                                <th>Description</th>
                                <th>Required</th>
                                <th>Max size</th></tr>
                            </thead>
                            <tbody>
                                <tr><td style="text-align: center;">1</td><td>Customer Name</td><td style="width:450px;">Customer Name should only be in letters - no numbers allowed</td><td style="text-align: center;">Yes</td><td style="text-align: center;">100</td></tr>
                                <tr><td style="text-align: center;">2</td><td>Email Id</td><td>Customer Email Id</td><td style="text-align: center;">Yes</td><td style="text-align: center;">100</td></tr>
                            </tbody>
                            </table>
                        </div>
                    </div>
                   <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                        <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" onClick="this.form.submit(); this.disabled=true; this.innerHTML='Loading';">Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
