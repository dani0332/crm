<div class="modal fade" id="createCarLeadModal" name="createCarLeadModal" tabindex="-1" role="dialog"
     aria-labelledby="createCarLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="create-payment-form" method="post"
                autocomplete="off">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" style="font-size: 16px !important;">
                        <i class="fa fa-cog" aria-hidden="true"></i>
                        <strong style="margin-left: 13px;">Create Lead</strong>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="col-md-12">
                        <fieldset id="tickLabels">
                            <legend>Select reason to create manual lead<span class="required">*</span></legend>
                                <div class="row">
                                    <div class="col-1">
                                        <input type="radio" data-confirmation="true" name="reason-manual-lead" value="referral" />
                                    </div>
                                    <span class="col-form-label col-md-3 col-sm-3">Referral</span>
                                </div>
                                <div class="row">
                                    <div class="col-1">
                                        <input type="radio" data-confirmation="true" name="reason-manual-lead" value="renewal-allocation" />
                                    </div>
                                    <span class="col-form-label col-md-3 col-sm-3">Advance Renewal Allocation</span>
                                </div>
                                <div class="row">
                                    <div class="col-1">
                                        <input type="radio" name="reason-manual-lead" value="ps-pending" />
                                    </div>
                                    <span class="col-form-label col-md-3 col-sm-3">Payment Status: Pending</span>
                                </div>
                                <div class="row">
                                    <div class="col-1">
                                        <input type="radio" name="reason-manual-lead" value="ps-authorized" />
                                    </div>
                                    <span class="col-form-label col-md-3 col-sm-3">Payment Status: Authorized </span>
                                </div>
                                <div class="row">
                                    <div class="col-1">
                                        <input type="radio" name="reason-manual-lead" value="ps-declined" />
                                    </div>
                                    <span class="col-form-label col-md-3 col-sm-3">Payment Status: Declined </span>
                                </div>
                                <div class="row">
                                    <div class="col-1">
                                        <input type="radio" name="reason-manual-lead" value="ps-failed" />
                                    </div>
                                    <span class="col-form-label col-md-3 col-sm-3">Payment Status: Failed </span>
                                </div>
                                <div class="row">
                                    <div class="col-1">
                                        <input type="radio" name="reason-manual-lead" value="ps-captured" />
                                    </div>
                                    <span class="col-form-label col-md-3 col-sm-3">Payment Status: Captured </span>
                                </div>
                                <div class="row">
                                    <div class="col-1">
                                        <input type="radio" name="reason-manual-lead" value="ps-cancelled" />
                                    </div>
                                    <span class="col-form-label col-md-3 col-sm-3">Payment Status: Cancelled </span>
                                </div>
                        </fieldset>
                        <p class="red const-lead-err-msg d-none">Unable to create manual lead, you can edit the same lead.</p>
                    </div>
                </div>
                <div class="modal-footer" style="justify-content: center;">
                    <button type="button" class="btn btn-sm btn-success const-car-lead-cnfrm-btn">Confirm</button>
                    <button type="button" data-dismiss="modal" class="btn btn-sm btn-danger">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
