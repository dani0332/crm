@extends('layouts.app')
@section('title','Upload Documents')
@section('content')
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Upload Documents - {{ $quoteCdbId }}</h2>
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

                @foreach ($documentUploadTypes as $key => $documentType)
                <table width="100%">
                    <tr>
                        <td width="25%">
                            <b>{{ ucwords($documentType->text) }}</b>
                            <div><small class="text-muted">
                            Allowed files: {{ $documentType->max_files }}<br>
                            Allowed formats: {{ $documentType->accepted_files }}<br>
                            Allowed upload size: {{ $documentType->max_size }} MB<br></small></div>
                        </td>
                        <td>
                            <div class="container">
                                <form method='post' action="{{ url('/quotes/'.$quoteType.'/uploadDocumentProcess') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left dropzone" id="{{ $documentType->code }}">
                                    {{csrf_field()}}
                                </form>
                            </div>
                        </td>
                    </tr>
                </table>
                <script type="text/javascript">
                    Dropzone.autoDiscover = false;
                    var myDropzone = new Dropzone('#{{ $documentType->code }}', {
                        paramName: "file",
                        url: "{{ url('/quotes/'.$quoteType.'/uploadDocumentProcess') }}",
                        maxFiles: JSON.parse('<?php echo json_encode($documentType->max_files) ?>'),
                        maxFilesize: JSON.parse('<?php echo json_encode($documentType->max_size) ?>'),
                        autoProcessQueue: true,
                        uploadMultiple: false,
                        acceptedFiles: JSON.parse('<?php echo json_encode($documentType->accepted_files) ?>'),
                        sending: function(file, xhr, formData) {
                            formData.append("_token", "{{{ csrf_token() }}}");
                            formData.append("quote_id", "{{ $quoteId }}");
                            formData.append("quote_type_id", "{{ $quoteTypeId }}");
                            formData.append("document_type_code", "{{ $documentType->code}}");
                            formData.append("folder_path", "{{ $documentType->folder_path }}");
                        },
                    });
                </script>
                @endforeach

                <script type="text/javascript">
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