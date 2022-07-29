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
                @foreach ($documentUploadTypes as $documentType)
                <table width="100%">
                    <tr>
                        <td width="25%" valign="middle">
                            <b>{{ ucwords($documentType->text) }}</b>
                            <div><small class="text-muted">
                                <table>
                                    <tr><td width="45%">Max file(s) </td><td>{{ $documentType->max_files }}</td></tr>
                                    <tr><td>Supported </td><td>{{ $documentType->accepted_files }}</td></tr>
                                    <tr><td>File max size </td><td>{{ $documentType->max_size }} MB</td></tr>
                                    <tr><td>Is Required? </td><td>{{ $documentType->is_required ? 'Yes' : 'No' }}</td></tr>
                                    <tr><td>Email Attach? </td><td>{{ $documentType->send_to_customer ? 'Yes' : 'No' }}</td></tr>
                                </table>
                            </small></div>
                        </td>
                        <td>
                            <div class="container">
                                <form method='post' enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left dropzone" id="{{ $documentType->code }}">
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
                        url: "{{ url('/quotes/'.$quoteType.'/'.$quoteUuId.'/documents/store') }}",
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
                            formData.append("quote_uuid", "{{ $quoteUuId }}");
                        },
                    });
                </script>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection