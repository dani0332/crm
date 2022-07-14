@extends('layouts.app')
@section('title','Upload Documents')
@section('content')
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
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
                    <div class="container">
                    <form method='post' action="{{ url('/quotes/'.$quoteType.'/uploadDocumentProcess') }}"" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left dropzone" autocomplete="off" id="dropzone">
                    {{csrf_field()}}
                    </div>
                    <input type="hidden" id="quote_id" name="quote_id" value="{{ $quoteId }}">
                    <input type="hidden" id="quote_type_id" name="quote_type_id" value="{{ $quoteTypeId }}">
                    
                    <br>

                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Policy wording <span class="required">*</span></span>
                            <input type="file" id="policy_wording" name="policy_wording" class="form-control form-control-sm" />
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Policy schedule <span class="required">*</span></span>
                            <input type="file" id="policy_schedule" name="policy_schedule" class="form-control form-control-sm" />
                        </div>
                    </div>

                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Debit note <span class="required">*</span></span>
                            <input type="file" id="debit_note" name="debit_note" class="form-control form-control-sm" />
                        </div>
                        <div class="col">
                        </div>
                    </div>

                    <br>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm">Upload</button>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    </form>
                
                <script type="text/javascript">
                Dropzone.options.dropzone =
                {
                    paramName: "file",
                    autoDiscover: false,
                    autoProcessQueue: false,
                    uploadMultiple: true,
                    maxFilesize: 5, // in mb
                    acceptedFiles: ".xlsm,.xlsx,.pdf,.jpeg,.jpg",
                    addRemoveLinks: true,
                    timeout: 60000,
                    renameFile: function (file) {
                        var dt = new Date();
                        var time = dt.getTime();
                        return time + file.name;
                    },
                    sending: function(file, xhr, formData) {
                        formData.append("_token", "{{{ csrf_token() }}}");
                    },
                    success: function (file, response) {
                        //alert('sdf');
                        console.log(response);
                    },
                    error: function (file, response) {
                        return false;
                    }
                };
                </script>
            </div>
        </div>
    </div>
</div>
@endsection