@php
use App\Enums\RolesEnum;
use App\Enums\DatabaseColumnsString;
@endphp
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Customer Additional Contacts</h2>
                @if(! auth()->user()->hasRole(RolesEnum::PA))
                    <button id="additional-contact-add-btn" class="btn btn-warning btn-sm" style="float:right;">Add Additional Contact</button>
                @endif
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
                                <td>{{ ucwords(str_replace("_", " ", $customerAdditionalContact->key)).'.' }}</td>
                                <td>{{ $customerAdditionalContact->value }}</td>
                                <td>{{ $customerAdditionalContact->created_at }}</td>
                                <td style="float:right;">
                                    @if(! auth()->user()->hasRole(RolesEnum::PA))
                                        @if($customerAdditionalContact->key == DatabaseColumnsString::EMAIL)
                                            <button class="btn btn-success btn-sm additional-email-make-primary-btn"
                                                data-quote-id="{{ $record->id }}" 
                                                data-key="{{ $customerAdditionalContact->key }}" 
                                                data-value="{{ $customerAdditionalContact->value }}" 
                                                data-quote-type="{{ $quoteType }}"
                                                data-quote-primary-email-address="{{ $record->email }}"
                                                data-quote-customer-id="{{ $record->customer_id }}"
                                                data-record-id="{{ $customerAdditionalContact->id }}">Make Primary</button>
                                        @else
                                            <button class="btn btn-success btn-sm additional-mobile-no-make-primary-btn"
                                                data-quote-id="{{ $record->id }}"
                                                data-key="{{ $customerAdditionalContact->key }}"
                                                data-value="{{ $customerAdditionalContact->value }}"
                                                data-quote-type="{{ $quoteType }}"
                                                data-quote-primary-mobile-no="{{ $record->mobile_no }}"
                                                data-quote-customer-id="{{ $record->customer_id }}">Make Primary</button>
                                        @endif
                                        @isset($customerAdditionalContact->id)
                                            @if(!isCarLostStatus($record->quote_status_id))
                                                    <button class="btn btn-danger btn-sm additional-contact-delete-btn" data-customer-additional-contact-id="{{ $customerAdditionalContact->id }}">Delete</button>
                                            @endif
                                        @endisset
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
