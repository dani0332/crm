<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Documents</h2>
                <a href="{{ url('quotes/'.$quoteType.'/'.$record->uuid.'/documents') }}" class="btn btn-primary btn-sm" style="float:right;">Upload Documents</a>
                @if($record->advisor_id == auth()->user()->id)
                <a href="{{ url('quotes/'.$quoteType.'/'.$record->uuid.'/send-policy-documents') }}" class="btn btn-primary btn-sm" style="float:right;">Send Policy</a>
                @endif
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th>Document Name</th>
                            <th>Created At</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quoteDocuments as $key => $document)
                            <tr>
                                <td>{{ $document->document_type_text ? $document->document_type_text : '' }}</td>
                                <td><a href="/documents/{{ $document->doc_uuid }}" target="_blank">{{ $document->doc_name }}</a></td>
                                <td>{{ $document->created_at }}</td>
                                <td>{{ $document->createdBy ? $document->createdBy->name : '' }}</td>
                                <td><button class="btn btn-sm btn-warning" 
                                        data-record-id="{{ $document->id }}" 
                                        data-record-quote-type="{{ $quoteType }}" 
                                        data-record-uuid="{{ $record->uuid }}" 
                                        onclick="deleteQuoteDocument(this)">Delete</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>