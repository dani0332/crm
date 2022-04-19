@extends('layouts.app')
@section('title','Upload Customers')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Upload Renewals</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif

                @if (session()->has('failures'))
                <div class="required"><b>Please correct below data and import it again separately, other data already imported.</b></div>
                <br />
                <table class="table table-danger">
                    <tr>
                        <th>Row</th>
                        <th>Column</th>
                        <th>Errors</th>
                        <th>Value</th>
                    </tr>
                    @foreach (session()->get('failures') as $validation)
                        <tr>
                            <td>{{ $validation->row() }}</td>
                            <td>{{ $validation->attribute()+1 }}</td>
                            <td>
                                <ul>
                                    @foreach ($validation->errors() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>
                                {{ $validation->values()[$validation->attribute()] }}
                            </td>
                        </tr>
                    @endforeach
                </table>
                @endif
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ url('renewals-process') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="File">File <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="file" id="file_name" name="file_name" class="form-control form-control-sm" accept='.xlsx' data-toggle="tooltip" data-placement="top" title="Please select .xlsx file to upload" />
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
                        <p><h4 class="required"><b>Import Instructions must be follow:</b></h4></p>
                        <p><ul class="required" style="font-weight:bold;">
                            <li>Download the sample xlsx file, modify the data according to the recommendations for a successful import.</li>
                            <li>File must be a xlsx file with the following fields.</li>
                            <li>Please ensure there are no commas in file.</li>
                            <li>First row will be skipped while uploading.</li>
                            <li>Please ensure there are no spaces in start and end of columns data</li>
                            <li>Please ensure max allowed size is 2mb (2048kb) & recommend numbers 500 leads per xlsx file.</li>
                            <li>Please ensure columns header and allocation same as per given in sample xlsx file.</li>
                            <li>Please ensure all required columns data filled in the xlsx file.</li>
                            <li>Arabic is not supported in xlsx file upload</li>
                        </ul></p>
                    </div>
                    <div class="item form-group">
                        <div class="col-md-3">
                            Download Sample XLSX <a href="https://myalfreddev.blob.core.windows.net/myrewards/94152CB2-D304-B775-9D49-6B09791F904B_sample1.xlsx"><img src="https://img.icons8.com/color/40/000000/ms-excel.png" alt="xlsx" border="0" /></a>
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
                                <tr><td style="text-align: center;">2</td><td>Customer Email</td><td>Customer Email Id</td><td style="text-align: center;">Yes</td><td style="text-align: center;">100</td></tr>
                                <tr><td style="text-align: center;">3</td><td>Type</td><td>Insurance Type</td><td style="text-align: center;">Yes</td><td style="text-align: center;">4</td></tr>
                                <tr><td style="text-align: center;">4</td><td>Insurer</td><td>Insurance Provider - should match the CDB data</td><td style="text-align: center;">Yes</td><td style="text-align: center;">50</td></tr>
                                <tr><td style="text-align: center;">5</td><td>Product</td><td>Quotation asked against the insurance line</td><td style="text-align: center;">Yes</td><td style="text-align: center;">100</td></tr>
                                <tr><td style="text-align: center;">6</td><td>Product Type</td><td>Type of Insurance | Comprehensive or Third Party Only</td><td style="text-align: center;">No</td><td style="text-align: center;">50</td></tr>
                                <tr><td style="text-align: center;">7</td><td>Sales Channel</td><td>Source of the quotation</td><td style="text-align: center;">Yes</td><td style="text-align: center;">100</td></tr>
                                <tr><td style="text-align: center;">8</td><td>Customer mobile</td><td>Customer Mobile Number - only numeric data</td><td style="text-align: center;">No</td><td style="text-align: center;">15</td></tr>
                                <tr><td style="text-align: center;">9</td><td>Advisor</td><td>Advisor Email</td><td style="text-align: center;">No</td><td style="text-align: center;">100</td></tr>
                                <tr><td style="text-align: center;">10</td><td>Previous Advisor</td><td>Previosuly assigned Advisor Email</td><td style="text-align: center;">No</td><td style="text-align: center;">100</td></tr>
                                <tr><td style="text-align: center;">11</td><td>Policy</td><td>Policy number assigned</td><td style="text-align: center;">Yes</td><td style="text-align: center;">100</td></tr>
                                <tr><td style="text-align: center;">12</td><td>Batch</td><td>Batch number assigned</td><td style="text-align: center;">No</td><td style="text-align: center;">25</td></tr>
                                <tr><td style="text-align: center;">13</td><td>Start Date</td><td>Start Date of the insurance - Format should be DD/MM/YYYY</td><td style="text-align: center;">No</td><td style="text-align: center;">10</td></tr>
                                <tr><td style="text-align: center;">14</td><td>End Date</td><td>End Date of the insurance - Format should be DD/MM/YYYY</td><td style="text-align: center;">Yes</td><td style="text-align: center;">10</td></tr>
                                <tr><td style="text-align: center;">15</td><td>Object</td><td>Information against the quotation</td><td style="text-align: center;">No</td><td style="text-align: center;">200</td></tr>
                                <tr><td style="text-align: center;">16</td><td>Gross Premium</td><td>Premium amount</td><td style="text-align: center;">No</td><td style="text-align: center;">25</td></tr>
                                <tr><td style="text-align: center;">17</td><td>Notes</td><td>Any other Information</td><td style="text-align: center;">No</td><td style="text-align: center;">200</td></tr>
                                <tr><td style="text-align: center;">18</td><td>Make</td><td>Car Make Information</td><td style="text-align: center;">No</td><td style="text-align: center;">50</td></tr>
                                <tr><td style="text-align: center;">19</td><td>Model</td><td>Car Model Information</td><td style="text-align: center;">No</td><td style="text-align: center;">50</td></tr>
                                <tr><td style="text-align: center;">20</td><td>Year</td><td>Vehicle Year of manufacture</td><td style="text-align: center;">No</td><td style="text-align: center;">4</td></tr>
                            </tbody>
                            </table>
                        </div>
                    </div>
                   <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                        <button type="submit" class="btn btn-warning btn-sm" id="renewals-upload-button">Upload</button>
                        <div id="renewals-upload-button-text" class="required" style="font-weight:bold;"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
