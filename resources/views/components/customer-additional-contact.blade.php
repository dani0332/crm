<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Customer Additional Contacts</h2>
                <button id="additional-contact-add-btn" class="btn btn-warning btn-sm" style="float:right;">Add Additional Contact</button>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Created At</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customerAdditionalContacts as $key => $customerAdditionalContact)
                            @if($record->email == $customerAdditionalContact->value || $record->mobile_no == $customerAdditionalContact->value)
                                @continue
                            @endif
                            <tr>
                                <td>{{ ucwords(str_replace("_", " ", $customerAdditionalContact->key)) }}</td>
                                <td>{{ $customerAdditionalContact->value }}</td>
                                <td>{{ $customerAdditionalContact->created_at }}</td>
                                <td style="float:right;">
                                    @if($customerAdditionalContact->key == 'email')
                                    <button class="btn btn-success btn-sm additional-email-make-primary-btn" 
                                            data-record-id="{{ $customerAdditionalContact->id }}" 
                                            data-quote-id="{{ $record->id }}" 
                                            data-key="{{ $customerAdditionalContact->key }}" 
                                            data-value="{{ $customerAdditionalContact->value }}" 
                                            data-quote-type="{{ $quoteType }}" 
                                            >Make Primary</button>
                                    @endif
                                    <button class="btn btn-danger btn-sm additional-contact-delete-btn" data-customer-additional-contact-id="{{ $customerAdditionalContact->id }}">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
