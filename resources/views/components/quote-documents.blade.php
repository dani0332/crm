<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Documents</h2>
                <a href="{{ url('quotes/'.$quoteType.'/'.$record->uuid.'/uploadDocument') }}" class="btn btn-primary btn-sm" style="float:right;">Upload Documents</a>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th>Document Name</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Created By</th>
                            <th>Updated By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quoteDocuments as $key => $document)
                            <tr>
                                <td>{{ $document->documentType ? $document->documentType->text : '' }}</td>
                                <td><a href="{{ $document->doc_url }}" target="_blank">{{ $document->doc_name }}</a></td>
                                <td>{{ $document->created_at }}</td>
                                <td>{{ $document->updated_at }}</td>
                                <td>{{ $document->createdBy ? $document->createdBy->name : '' }}</td>
                                <td>{{ $document->updatedBy ? $document->updatedBy->name : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>