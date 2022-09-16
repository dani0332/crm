<div class="modal fade" id="customer-additional-email-modal" name="customer-additional-email-modal" tabindex="-1" role="dialog"
        aria-labelledby="customer-additional-email-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <form method="post" action="{{ url('/quotes/car/addNoteForCustomer') }}" autocomplete="off">
                    {{ csrf_field() }}
                    @method('POST')
                    <div class="modal-header">
                        <h5 class="modal-title" id="customer-additional-email-modal-label" style="font-size: 16px !important;"> <i class="fa fa-cog" aria-hidden="true"></i>
                            <strong style="margin-left: 13px;">Customer New Additional Email Address</strong> 
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="col-md-12" id="followup-div">
                        <div class="col">
                            <div class="input-group">
                                <input type="text" id="additional_email" name="additional_email" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="justify-content: center;">
                    <table style="text-align: center;">
                        <tr><td><div><button type="submit" class="btn btn-sm btn-warning" id="additional-email-modal-add-btn">Add</button></div></td></tr>
                        <tr><td><div id="additional-email-validation-msg"></div></td></tr>
                    </table>
                    </div>
                </form>
            </div>
        </div>
    </div>