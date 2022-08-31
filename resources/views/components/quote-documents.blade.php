@php
use App\Enums\PermissionsEnum;
@endphp
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Documents</h2>
                @cannot(PermissionsEnum::ApprovePayments)
                <a href="{{ url('quotes/'.$quoteType.'/'.$record->uuid.'/documents') }}" class="btn btn-primary btn-sm" style="float:right;">Upload Documents</a>
                @if($record->advisor_id == auth()->user()->id && isset($displaySendPolicyButton) && $displaySendPolicyButton)
                <a class="btn btn-sm btn-primary" style="float:right;" data-quote-type="{{ $quoteType }}"
                data-quote-uuid="{{ $record->uuid }}" onclick="sendQuoteDocumentsToCustomer(this)">Send Policy</a>
                <br clear="all" />
                <div class="alert alert-success" id="email-send-success" style="display:none;">Email sent. Page will be refresh in 5 seconds. Please check email status table for further detail.</div>
                <div class="alert alert-success" id="document-delete-success" style="display:none;">Document deleted. Page will be refresh now.</div>
                @endif
                @endcannot
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
                                <td>
                                    @cannot(PermissionsEnum::ApprovePayments)


                                    <button class="btn btn-sm btn-warning"
                                        data-record-id="{{ $document->id }}"
                                        data-record-quote-type="{{ $quoteType }}"
                                        data-record-uuid="{{ $record->uuid }}"
                                        onclick="deleteQuoteDocument(this)">Delete</button>
                                        @endcannot
                                    </td>


                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
