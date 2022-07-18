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
                <p><b>CdbId:</b> {{ $quoteCdbId }}</p>
                <table width="100%">
                    <tr>
                        <td width="25%">
                            <b>Policy Schedule</b>
                            <div><small class="text-muted">
                            Allowed files: 1<br>
                            Allowed formats: .xlsm, .xlsx, .pdf, .jpeg, .jpg<br>
                            Allowed upload size: 5MB<br></small></div>
                        </td>
                        <td>
                            <div class="container">
                                <form method='post' action="{{ url('/quotes/'.$quoteType.'/uploadDocumentProcess') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left dropzone" id="policy-schedule">
                                    {{csrf_field()}}
                                    <input type="hidden" id="quote_id" name="quote_id" value="{{ $quoteId }}">
                                    <input type="hidden" id="quote_type_id" name="quote_type_id" value="{{ $quoteTypeId }}">
                                </form>
                            </div>
                        </td>
                    </tr>
                </table>

                <table width="100%">
                    <tr>
                        <td width="25%">
                            <b>Debit Note</b>
                            <div><small class="text-muted">
                            Allowed files: 1<br>
                            Allowed formats: .xlsm, .xlsx, .pdf, .jpeg, .jpg<br>
                            Allowed size: 5MB<br></small></div>
                        </td>
                        <td>
                            <div class="container">
                                <form method='post' action="{{ url('/quotes/'.$quoteType.'/uploadDocumentProcess') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left dropzone" id="debit-note">
                                    {{csrf_field()}}
                                    <input type="hidden" id="quote_id" name="quote_id" value="{{ $quoteId }}">
                                    <input type="hidden" id="quote_type_id" name="quote_type_id" value="{{ $quoteTypeId }}">
                                </form>
                            </div>
                        </td>
                    </tr>
                </table>

                <table width="100%">
                    <tr>
                        <td width="25%">
                            <b>Emirates Id</b>
                            <div><small class="text-muted">
                            Allowed files: 2<br>
                            Allowed formats: .xlsm, .xlsx, .pdf, .jpeg, .jpg<br>
                            Allowed size: 5MB<br></small></div>
                        </td>
                        <td>
                            <div class="container">
                                <form method='post' action="{{ url('/quotes/'.$quoteType.'/uploadDocumentProcess') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left dropzone" id="emirates-id">
                                    {{csrf_field()}}
                                    <input type="hidden" id="quote_id" name="quote_id" value="{{ $quoteId }}">
                                    <input type="hidden" id="quote_type_id" name="quote_type_id" value="{{ $quoteTypeId }}">
                                </form>
                            </div>
                        </td>
                    </tr>
                </table>

                <table width="100%">
                    <tr>
                        <td width="25%">
                            <b>Other</b>
                            <div><small class="text-muted">
                            Allowed files: 20<br>
                            Allowed formats: .xlsm, .xlsx, .pdf, .jpeg, .jpg<br>
                            Allowed size: 5MB<br></small></div>
                        </td>
                        <td>
                            <div class="container">
                                <form method='post' action="{{ url('/quotes/'.$quoteType.'/uploadDocumentProcess') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left dropzone" id="other">
                                    {{csrf_field()}}
                                    <input type="hidden" id="quote_id" name="quote_id" value="{{ $quoteId }}">
                                    <input type="hidden" id="quote_type_id" name="quote_type_id" value="{{ $quoteTypeId }}">
                                </form>
                            </div>
                        </td>
                    </tr>
                </table>

                <script type="text/javascript">
                    Dropzone.autoDiscover = false;

                    var policySchedule = new Dropzone('#policy-schedule', {
                        url: "{{ url('/quotes/'.$quoteType.'/uploadDocumentProcess') }}",
                        maxFiles: 1,
                        autoProcessQueue: true,
                        addRemoveLinks: true,
                        uploadMultiple: false,
                        acceptedFiles: '.xlsm,.xlsx,.pdf,.jpeg,.jpg'
                    });

                    var debitNote = new Dropzone('#debit-note', {
                        url: "/file/post",
                        maxFiles: 1,
                        autoProcessQueue: true,
                        addRemoveLinks: true,
                        uploadMultiple: false,
                        acceptedFiles: '.xlsm,.xlsx,.pdf,.jpeg,.jpg'
                    });

                    var emiratesId = new Dropzone('#emirates-id', {
                        url: "/file/post",
                        maxFiles: 2,
                        autoProcessQueue: true,
                        addRemoveLinks: true,
                        uploadMultiple: false,
                        acceptedFiles: '.xlsm,.xlsx,.pdf,.jpeg,.jpg'
                    });

                    var other = new Dropzone('#other', {
                        url: "/file/post",
                        maxFiles: 20,
                        autoProcessQueue: true,
                        addRemoveLinks: true,
                        uploadMultiple: true,
                        acceptedFiles: '.xlsm,.xlsx,.pdf,.jpeg,.jpg'
                    });
                // Dropzone.options.dropzone =
                // {
                //     paramName: "file",
                //     autoDiscover: false,
                //     autoProcessQueue: true,
                //     uploadMultiple: true,
                //     maxFilesize: 5, // in mb
                //     acceptedFiles: ".xlsm,.xlsx,.pdf,.jpeg,.jpg",
                //     addRemoveLinks: true,
                //     timeout: 60000,
                //     renameFile: function (file) {
                //         var dt = new Date();
                //         var time = dt.getTime();
                //         return time + file.name;
                //     },
                //     sending: function(file, xhr, formData) {
                //         formData.append("_token", "{{{ csrf_token() }}}");
                //     },
                //     success: function (file, response) {
                //         //alert('sdf');
                //         console.log(response);
                //     },
                //     error: function (file, response) {
                //         return false;
                //     }
                // };
                </script>
            </div>
        </div>
    </div>
</div>
@endsection