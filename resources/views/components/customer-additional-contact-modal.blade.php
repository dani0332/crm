@php
    use App\Enums\GenericRequestEnum;
@endphp
<div class="modal fade" id="customer-additional-contact-add-modal" name="customer-additional-contact-add-modal" tabindex="-1" role="dialog"
        aria-labelledby="customer-additional-contact-add-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customer-additional-contact-add-modal-label" style="font-size: 16px !important;"> <i class="fa fa-cog" aria-hidden="true"></i>
                        <strong style="margin-left: 13px;">Customer New Additional Contact</strong> 
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="col-md-12">
                        <div class="col">
                            <div class="input-group">
                                <label class="col-form-label col-md-3 col-sm-3 label-align">Type <span class="required">*</span></label>
                                <select name="additional_contact_type" id="additional_contact_type" class="form-control">
                                    <option value="email">Email</option>
                                    <option value="mobile_no">Mobile Number</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group">
                                <label class="col-form-label col-md-3 col-sm-3 label-align">Value <span class="required">*</span></label>
                                <input type="text" id="additional_contact" name="additional_contact" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="justify-content: center;">
                    <table style="text-align: center;">
                        <tr><td><div id="additional-contact-modal-validation-msg" class="required"></div></td></tr>
                        <tr><td> </td></tr>
                        <tr><td> </td></tr>
                        <tr><td>
                            <button type="submit" class="btn btn-sm btn-warning" id="additional-contact-modal-add-btn" 
                            data-quote-id="{{ $record->id }}" 
                            data-quote-type="{{ $quoteType }}" 
                            data-customer-id="{{ $record->customer_id }}" 
                            data-contact-type-email-enum="{{ GenericRequestEnum::EMAIL }}" 
                            data-contact-type-mobile-no-enum="{{ GenericRequestEnum::MOBILE_NO }}" 
                            >Add Contact</button>
                        </td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>