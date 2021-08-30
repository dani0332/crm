@extends('layouts.app')
@section('title','Upload TM Leads')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Upload TM Leads</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('tmuploadlead.index') }}" class="btn btn-warning btn-sm">Upload TM Leads List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ url('telemarketing/tmuploadlead') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="File">File <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                        <input type="file" id="file_name" name="file_name" class="form-control form-control-sm" accept='.csv' data-toggle="tooltip" data-placement="top" title="Please select .csv file to upload" />
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
                            <li>File must be a csv file with the following fields.</li>
                            <li><b>Please ensure there are no commas in file.</b></li>
                            <li>First line will be skipped while uploading.</li>
                            <li>Please ensure there are no spaces in start and end of columns data</li>
                            <li>Please ensure max allowd size is 2mb (2048kb)</li>
                        </ul></p>
                    </div>
                    <div class="item form-group">
                    Download Sample CSV <a href="https://myalfreddev.blob.core.windows.net/myrewards/F7CFFA3D-CD95-3E3E-A142-3F21E2CC94DC_TestTeleLead.xlsx"><img src="https://i.ibb.co/cv5WptT/csv.png" alt="csv" border="0" /></a>
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
                                <tr><td style="text-align: center;">1</td><td>Customer Name</td><td style="width:450px;">Customer Name should only be in letters - no numbers allowed</td><td style="text-align: center;">Yes</td><td style="text-align: center;">50</td></tr>
                                <tr><td style="text-align: center;">2</td><td>Phone No</td><td style="width:450px;">Customer Phone No </td><td style="text-align: center;">Yes</td><td style="text-align: center;">12</td></tr>
                                <tr><td style="text-align: center;">3</td><td>Email Id</td><td>Customer Email Id</td><td style="text-align: center;">Yes</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">4</td><td>Insurance Type</td><td>Insurance Type description of the lead, must be exact match with Insurance Type List</td><td style="text-align: center;">Yes</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">5</td><td>Allocation Date</td><td>Allocation Date must in the format of DD-MM-YYYY </td><td style="text-align: center;">Yes</td><td style="text-align: center;">10</td></tr>
                                <tr><td style="text-align: center;">6</td><td>Enquiry Date</td><td>Enquiry Date must in the format of DD-MM-YYYY </td><td style="text-align: center;">Yes</td><td style="text-align: center;">10</td></tr>
                                <tr><td style="text-align: center;">7</td><td>DOB</td><td>DOB must in the format of DD-MM-YYYY </td><td style="text-align: center;">No</td><td style="text-align: center;">10</td></tr>
                                <tr><td style="text-align: center;">8</td><td>Nationality</td><td>Nationality must be exact match with Nationality List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">9</td><td>Years of driving</td><td>Years of driving must be exact match with Years of driving List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">10</td><td>Car Make</td><td>Car Make must be exact match with Car Make List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">11</td><td>Car Model</td><td>Car Model must be exact match with Car Model List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">12</td><td>Year of manufacture</td><td>Year of manufacture must be exact match with Year of manufacture List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">13</td><td>Emirates of registration</td><td>Emirates of registration must be exact match with Emirates of registration List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">14</td><td>Car Value </td><td>Car value must be in decimal </td><td style="text-align: center;">No</td><td style="text-align: center;">10</td></tr>
                                <tr><td style="text-align: center;">15</td><td>Notes</td><td>Additional Notes</td><td style="text-align: center;">No</td><td style="text-align: center;">500</td></tr>
                                <tr><td style="text-align: center;">16</td><td>Assigned Advisor's email</td><td>Advisor Email Id</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                            </tbody>
                            </table>
                        </div>
                    </div>
                   <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                        <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
