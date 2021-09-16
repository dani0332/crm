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
                            <li>Please ensure there are no commas in file.</li>
                            <li>First line will be skipped while uploading.</li>
                            <li>Please ensure there are no spaces in start and end of columns data.</li>
                            <li>Please ensure max allowed size is 2mb (2048kb).</li>
                        </ul></p>
                    </div>
                    <div class="item form-group">
                        Download Sample CSV <a href="https://myalfreddev.blob.core.windows.net/myrewards/4D621049-AB8F-2C0F-55B9-DB9DC74BD2C0_TestTeleLead.csv"><img src="https://i.ibb.co/cv5WptT/csv.png" alt="csv" border="0" /></a>
                    </div>
                    <div class="item form-group">
                        <span class="required"><b>Imporant Note:</b> Please download the sample csv file and modify the data by following the recommendations for a successful import.</span>
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
                                <tr><td style="text-align: center;">1</td><td>Customer Name</td><td style="width:450px;">Customer Name should only be in letters - no numbers allowed</td><td style="text-align: center;"><b class="required">Yes</b></td><td style="text-align: center;">50</td></tr>
                                <tr><td style="text-align: center;">2</td><td>Phone No</td><td style="width:450px;">Customer Phone No </td><td style="text-align: center;"><b class="required">Yes</b></td><td style="text-align: center;">12</td></tr>
                                <tr><td style="text-align: center;">3</td><td>Email Id</td><td>Customer Email Id must be valid Email Id</td><td style="text-align: center;"><b class="required">Yes</b></td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">4</td><td>Insurance Type</td><td>Insurance Type description of the lead, must be exact match with Insurance Type List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">5</td><td>Lead Type</td><td>Lead Type of the lead, must be exact match with Lead Type List</td><td style="text-align: center;"><b class="required">Yes</b></td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">6</td><td>Nationality</td><td>Nationality must be exact match with Nationality List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">7</td><td>DOB</td><td>DOB must in the format of YYYY-MM-DD (Ex:- 2019-12-14)</td><td style="text-align: center;">No</td><td style="text-align: center;">10</td></tr>
                                <tr><td style="text-align: center;">8</td><td>Years of driving</td><td>Years of driving must be exact match with Years of driving List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">9</td><td>Car Manufacturer</td><td>Car Make must be exact match with Car Make List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">10</td><td>Model</td><td>Car Model must be exact match with Car Model List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">11</td><td>Year of manufacture</td><td>Year of manufacture must be exact match with Year of manufacture List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">12</td><td>Emirates of registration</td><td>Emirates of registration must be exact match with Emirates of registration List</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">13</td><td>Car Value</td><td>Car value must be in decimal </td><td style="text-align: center;">No</td><td style="text-align: center;">10</td></tr>
                                <tr><td style="text-align: center;">14</td><td>Notes</td><td>Additional Notes</td><td style="text-align: center;">No</td><td style="text-align: center;">500</td></tr>
                                <tr><td style="text-align: center;">15</td><td>Enquiry Date</td><td>Enquiry Date must in the format of YYYY-MM-DD (Ex:- 2019-12-14)</td><td style="text-align: center;"><b class="required">Yes</b></td><td style="text-align: center;">10</td></tr>
                                <tr><td style="text-align: center;">16</td><td>Created Date</td><td>Created Date must in the format of YYYY-MM-DD (Ex:- 2019-12-14)</td><td style="text-align: center;"><b class="required">Yes</b></td><td style="text-align: center;">10</td></tr>
                                <tr><td style="text-align: center;">17</td><td>Advisor email</td><td>Advisor Email Id must be valid Email Id</td><td style="text-align: center;">No</td><td style="text-align: center;">30</td></tr>
                                <tr><td style="text-align: center;">18</td><td>Followup Date</td><td>Followup Date must in the format of YYYY-MM-DD (Ex:- 2019-12-14)</td><td style="text-align: center;">No</td><td style="text-align: center;">10</td></tr>
                                <tr><td style="text-align: center;">19</td><td>Followup Time</td><td>Followup Time must in the format of HH:MM:SS (Ex:- 18:38:36)</td><td style="text-align: center;">No</td><td style="text-align: center;">10</td></tr>
                            </tbody>
                            </table>
                        </div>
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
