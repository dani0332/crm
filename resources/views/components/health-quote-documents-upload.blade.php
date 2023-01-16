@extends('layouts.app')
@section('title','Upload Documents')
@section('content')
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<style>
	.dz-size {
		display: none;
	}
</style>
@php
    use \App\Enums\DocumentTypeCategory;
    $documentTypes = collect($documentTypes)->groupBy('category');


@endphp
<div class="row">

    <div class="col-md-12">

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">Home</div>
            <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">Profile</div>
            <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">Contact</div>
        </div>
    </div>

	<div class="col-md-12 col-sm-12 ">
		<div class="x_panel">
			<div class="x_title">
				<h2>Upload Documents - {{ $quoteCdbId }}</h2>
				<ul class="nav navbar-right panel_toolbox">
					<li><a href="{{ url('quotes/'.$quoteType.'/'.$quoteUuId.'') }}" class="btn btn-warning btn-sm">Go Back</a></li>
				</ul>
				<div class="clearfix"></div>
			</div>
			<div class="x_content">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                
                @foreach($quote->members as $member)
                    <li class="nav-item">
                        <a class="nav-link" id="home-tab" data-toggle="tab" href="#member-{{$member->id}}" role="tab" aria-controls="home" aria-selected="true">{{$member->memberCategory->text}}</a>
                    </li>
                @endforeach
                </ul>

                <div class="tab-content" id="myTabContent">

                    {{-- documents upload for members --}}
                    @foreach($quote->members as $member)
                        @php //dd($member->toArray()); @endphp
                        <div class="tab-pane fade show" id="member-{{$member->id}}" role="tabpanel" aria-labelledby="home-tab">
                            <table width="100%">
                                @foreach($documentTypes[DocumentTypeCategory::MEMBER] as $documentType)
                                    <tr height="150px">
                                        <td width="25%" valign="middle">
                                            <b>{{ ucwords($documentType->text) }}
                                                @if($documentType->is_required)
                                                    <span class="required">*</span>
                                                @else
                                                    <span> </span>
                                                @endif
                                            </b>
                                            <div><small class="text-muted">
                                                    <table>
                                                        <tr>
                                                            <td width="50%">Max file(s) </td>
                                                            <td>{{ $documentType->max_files }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Supported </td>
                                                            <td>{{ $documentType->accepted_files }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>File max size </td>
                                                            <td>{{ $documentType->max_size }} MB</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Email Attachment </td>
                                                            <td>{{ $documentType->send_to_customer ? 'Yes' : 'No' }}</td>
                                                        </tr>
                                                    </table>
                                                </small></div>
                                        </td>
                                        <td>
                                            <div class="container">
                                                <form method='post' enctype="multipart/form-data" class="dropzone"
                                                      id="member-{{ $member->id . $documentType->code }}">
                                                    {{csrf_field()}}
                                                </form>
                                                <script type="text/javascript">
                                                    Dropzone.autoDiscover = false;
                                                    var myDropzone = new Dropzone('#member-{{ $member->id . $documentType->code }}', {
                                                        paramName: "file",
                                                        url: "{{ url('/quotes/'.$quoteType.'/documents/store') }}",
                                                        maxFiles: {{ $documentType->max_files }},
                                                        maxFilesize: {{$documentType->max_size}},
                                                        autoProcessQueue: true,
                                                        uploadMultiple: false,
                                                        acceptedFiles: '{{$documentType->accepted_files}}',
                                                        addRemoveLinks: true,
                                                        dictDefaultMessage: "<b>Drop files here or click to upload.</b>",
                                                        dictRemoveFileConfirmation: "Are you sure to delete this document?",
                                                        sending: function(file, xhr, formData) {
                                                            formData.append("_token", "{{{ csrf_token() }}}");
                                                            formData.append("quote_id", "{{ $quoteId }}");
                                                            formData.append("quote_type_id", "{{ $quoteTypeId }}");
                                                            formData.append("document_type_code", "{{ $documentType->code}}");
                                                            formData.append("folder_path", "{{ $documentType->folder_path }}");
                                                            formData.append("quote_uuid", "{{ $quoteUuId }}");
                                                            formData.append("member_detail_id", "{{ $member->id }}");
                                                        },
                                                        init: function() {
                                                            let myDropzone = this;
                                                            $.get("/quotes/{{$quoteType}}/{{$quoteId}}/documents/{{$documentType->code}}/get-uploaded?member_id={{$member->id}}",
                                                                function(data) {
                                                                    $.each(data, function(key, value) {
                                                                        var docName = value.doc_name;
                                                                        var mockFile = {
                                                                            name: docName.split('_').pop(),
                                                                            fileName: docName,
                                                                            type: value.doc_mime_type,
                                                                            accepted: false,
                                                                        };
                                                                        myDropzone.options.addedfile.call(myDropzone, mockFile);
                                                                        myDropzone.options.complete.call(myDropzone, mockFile);

                                                                        if (key == data.length - 1) {
                                                                            myDropzone.options.maxFiles = myDropzone.options.maxFiles -
                                                                                data.length;
                                                                        }
                                                                    });
                                                                });

                                                            this.on('success', function(file) {
                                                                alert("File Uploaded Successfully");
                                                                location.reload();
                                                            });

                                                            this.on('maxfilesexceeded', function(file) {
                                                                alert("You can only upload a maximum of {{$documentType->max_files}} files");
                                                                location.reload();
                                                            });

                                                            this.on('removedfile', function(file) {
                                                                var docName = file.fileName;
                                                                $.ajax({
                                                                    type: 'POST',
                                                                    url: "{{ url('/documents/delete') }}",
                                                                    data: {
                                                                        docName: docName,
                                                                        quoteId: {{ $quoteId }},
                                                                        _token: '{{ csrf_token() }}'
                                                                    },
                                                                    success: function(data) {
                                                                        alert(data.message);
                                                                        $(".loader").show();
                                                                        location.reload();
                                                                    }
                                                                });
                                                            });
                                                        },
                                                    });
                                                </script>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    @endforeach
                </div>
			</div>
		</div>
	</div>
</div>
@endsection
