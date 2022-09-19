<div class="modal fade" id="customer-additional-contact-add-modal" name="customer-additional-contact-add-modal" tabindex="-1" role="dialog"
        aria-labelledby="customer-additional-contact-add-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <form method="post" action="{{ url('/quotes/car/addNoteForCustomer') }}" autocomplete="off">
                    {{ csrf_field() }}
                    @method('POST')
                    <div class="modal-header">
                        <h5 class="modal-title" id="customer-additional-contact-add-modal-label" style="font-size: 16px !important;"> <i class="fa fa-cog" aria-hidden="true"></i>
                            <strong style="margin-left: 13px;">Customer New Additional Contact</strong> 
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align">Type <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <select name="additional_contact_type" id="additional_contact_type" class="form-control">
                                <option value="email">Email</option>
                                <option value="mobile_no">Mobile Number</option>
                            </select>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align">Value <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="additional_contact" name="additional_contact" class="form-control">
                        </div>
                    </div>

                    <div class="modal-footer" style="justify-content: center;">
                    <table style="text-align: center;">
                        <tr><td><div id="additional-contact-modal-validation-msg" class="required"></div></td></tr>
                        <tr><td><div><button type="submit" class="btn btn-sm btn-warning" id="additional-contact-modal-add-btn">Add Contact</button></div></td></tr>
                    </table>
                    </div>
                </form>
            </div>
        </div>
    </div>