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
                <table width="100%">
                @foreach ($documentUploadTypes as $documentType)
                    <tr height="150px">
                        <td width="25%" valign="middle">
                            <b>{{ ucwords($documentType->text) }}</b>
                            <div><small class="text-muted">
                                <table>
                                    <tr><td width="50%">Max file(s) </td><td>{{ $documentType->max_files }}</td></tr>
                                    <tr><td>Supported </td><td>{{ $documentType->accepted_files }}</td></tr>
                                    <tr><td>File max size </td><td>{{ $documentType->max_size }} MB</td></tr>
                                    <tr><td>Is Required? </td><td>{{ $documentType->is_required ? 'Yes' : 'No' }}</td></tr>
                                    <tr><td>Customer Document? </td><td>{{ $documentType->send_to_customer ? 'Yes' : 'No' }}</td></tr>
                                </table>
                            </small></div>
                        </td>
                        <td>
                            <div class="container">
                                @if($documentType->max_files > $documents->where('document_type_code',$documentType->code)->count())
                                <form method='post' enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left dropzone" id="{{ $documentType->code }}">
                                    {{csrf_field()}}
                                </form>
                                <script type="text/javascript">
                                    Dropzone.autoDiscover = false;
                                    var myDropzone = new Dropzone('#{{ $documentType->code }}', {
                                        paramName: "file",
                                        url: "{{ url('/quotes/'.$quoteType.'/documents/store') }}",
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
                                        complete: function(file) {
                                            //window.location.reload();
                                        },
                                    });
                                </script>
                                @else
                                    <p align="center">Document(s) already uploaded. <br>If you need to replace it, please go back to delete the document & upload it again.</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
@endsection