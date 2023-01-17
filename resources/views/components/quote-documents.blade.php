@php
use App\Enums\PermissionsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RolesEnum;
$docUploadUrl = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL') . $record->uuid . '/thankyou';
@endphp
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Documents</h2>
                @cannot(PermissionsEnum::ApprovePayments)
                    @if(! auth()->user()->hasRole(RolesEnum::PA))
                        <a href="{{ url('quotes/'.$quoteType.'/'.$record->uuid.'/documents') }}" class="btn btn-primary btn-sm" style="float:right;">Upload Documents</a>
                    @endif
                    @if(isset($displaySendPolicyButton) && $displaySendPolicyButton && ! auth()->user()->hasRole(RolesEnum::PA))
                        <a class="btn btn-sm btn-primary" style="float:right;" data-quote-type="{{ $quoteType }}"
                            data-quote-uuid="{{ $record->uuid }}" onclick="sendQuoteDocumentsToCustomer(this)">Send Policy</a>
                        <br clear="all" />
                        <div class="alert alert-success" id="email-send-success" style="display:none;">Email sent. Page will be refresh in 10 seconds. Please check email status table for further detail.</div>
                        <div class="alert alert-danger" id="document-delete-success" style="display:none;">Document deleted. Page will be refresh now.</div>
                    @endif
                @endcannot

                @if($record->payment_status_id == PaymentStatusEnum::AUTHORISED && ! auth()->user()->hasRole(RolesEnum::PA))
                    <button id="btn_copy_doc_upload_link" data-label="Copy Upload Link" data-doc-upload-url="{{ $docUploadUrl }}" class="btn btn-success btn-sm pull-right">Copy Upload Link</button>
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
                                <td><a href="/documents/{{ $document->doc_uuid }}" target="_blank">{{ Str::of($document->doc_name)->explode('_')->last() }}</a></td>
                                <td>{{ $document->created_at }}</td>
                                <td>{{ $document->createdBy ? $document->createdBy->name : '' }}</td>
                                <td>
                                    @cannot(PermissionsEnum::ApprovePayments)
                                        @if(! auth()->user()->hasRole(RolesEnum::PA))
                                        <button class="btn btn-sm btn-warning"
                                            data-document-name="{{ $document->doc_name }}"
                                            data-quote-id="{{ $record->id }}"
                                            onclick="deleteQuoteDocument(this)">Delete</button>
                                        @endif
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
