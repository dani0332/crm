<style>
.radio-reason-manual-lead {
    margin-left: 25px;
}
.reason-manual-lead-label {
    text-decoration-line: underline;
    text-decoration-style: dotted;
}
</style>
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
                                <div class="row radio-reason-manual-lead">
                                    <input type="radio" data-confirmation="true" name="reason-manual-lead" value="referral" />
                                    <span class="col-form-label col-md-3 col-sm-3 reason-manual-lead-label" data-html="true" data-toggle="tooltip" data-placement="right" 
                                        title="<div class='wide-tip'>Select this to create a new lead for any<br/>referral from a client, 
                                        colleague or<br/>friend.<br/><br/>You may also select this option for<br/>creating a new lead for an existing<br/>
                                        client's new business.<br/>Example: An existing client wants to<br/>insure another car.</div>">Referral</span>
                                </div>
                                <div class="row radio-reason-manual-lead">
                                    <input type="radio" name="reason-manual-lead" value="early-renewal" />
                                    <span class="col-form-label col-md-3 col-sm-3 reason-manual-lead-label" data-html="true" data-toggle="tooltip" data-placement="right" 
                                        title="<div class='wide-tip'>If the client has a valid motor policy<br/>
                                        with InsuranceMarket.ae and they want<br/>to renew their policy earlier, please<br/>
                                        contact your manager immediately.<br/><br/>Your manager will allocate the renewal<br/>
                                        lead to you for further action.<br/><br/>There is no need to create a new lead<br/>for any early renewal.</div>">Early Renewal</span>
                                </div>
                                <div class="row radio-reason-manual-lead">
                                    <input type="radio" name="reason-manual-lead" value="payment-status" />
                                    <span class="col-form-label col-md-3 col-sm-3 reason-manual-lead-label" data-html="true" data-toggle="tooltip" data-placement="right" title="<div class='wide-tip'>If the payment status is ‘Pending’,<br/>
                                        ‘Authorized', ‘Declined', 'Failed’<br/>‘Captured’ or ‘Cancelled’; you do not<br/>
                                        need to create a new lead as you can<br/>work with the current lead.<br/><br/>
                                        You should be able to add manual<br/>plan(s), update or make changes to the<br/>
                                        available plan(s), add optional cover(s),<br/>and change of insurer using the same<br/>
                                        lead.<br/><br/>For 'Declined', 'Failed' or 'Pending', ask<br/>
                                        the client to retry the payment using the<br/>same or a different card.</div>">Payment Status</span>
                                </div>
                        </fieldset>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-success const-car-lead-cnfrm-btn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>
