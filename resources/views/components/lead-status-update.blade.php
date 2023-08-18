@php
use App\Enums\QuoteStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Enums\QuoteTypeId;
use App\Enums\LeadSourceEnum;

@endphp

<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
    $(document).ready(function() {
        var showFollowupStatuses = ['Followed Up', 'Qualification Pending', 'Quoted', 'FTC Pending', 'FTC Sent', 'Missing Documents Requested', 'Policy Documents Pending', 'Payment Pending', 'Pending with UW', 'Application Pending', 'In Negotiation'];
        if (showFollowupStatuses.find((str) => str == $('#leadStatus option:selected').text())) {
            $('#followup-div').show();
        }
        if ($('#leadStatus option:selected').text() == 'Lost') {
            $('#lost-reason-div').show();
        } else {
            $('#followup-div').hide();
        }
        if ($('#trans-div').find('.text-danger').text() != '') {
            $('#trans-div').show();
        }
        if ($('#lost-reason-div').find('.text-danger').text() != '') {
            $('#lost-reason-div').show();
        }
        var followupDate = '<?php echo $lead->next_followup_date; ?>';
        if (followupDate != '') {
            $('#followup-date').val(followupDate);
            $('#followup-div').show();
        }
        $("#nextFollowUpDate").daterangepicker({
            timePicker: true,
            singleDatePicker: true,
            timePicker24Hour: true,
            minDate: followupDate != '' ?
                new Date(followupDate).toLocaleDateString("en-US") : new Date().toLocaleDateString("en-US"),
            locale: {
                format: 'DD-MM-YYYY HH:mm:ss'
            }
        });

        $('#leadStatus').on('change', function() {
            var lead_status_code = $(this).val();
            if (showFollowupStatuses.find((str) => str == $('#leadStatus option:selected').text())) {
                $('#followup-div').show();
            } else {
                $('#followup-div').hide();
            }
            if ($("#leadStatus option:selected").text() == 'Transaction Approved') {
                $('#trans-div').show();
                $('#lost-reason-div').hide();
            } else if ($("#leadStatus option:selected").text() == 'Lost') {
                $('#lost-reason-div').show();
                $('#trans-div').hide();
            } else {
                $('#lost-reason-div').hide();
                $('#trans-div').hide();
            }

            if ($(this).val() == '{{QuoteStatusEnum::CarSold}}' || $(this).val() == '{{QuoteStatusEnum::Uncontactable}}') {
                $('.div-proof-document').show();
            } else {
                $('.div-proof-document').hide();
            }

            // Car Quote: on lead_status change hideshow next_followup_date conditionally
            next_followup_date_visibility(lead_status_code);
        });

        var quoteTypeId = '{{ $quoteTypeId }}';
        var quoteTypeCar = '{{ QuoteTypeId::Car }}';
        var isLeadPoolRole = '{{ auth()->user()->hasAnyRole([RolesEnum::LeadPool, RolesEnum::Admin]) }}';
        var isPaRole = '{{ auth()->user()->hasAnyRole([RolesEnum::PA, RolesEnum::Admin]) }}';
        var renewal_batch = '{{ $lead->renewal_batch }}';
        var source = '{{ $lead->source }}';
        var previous_quote_policy_number = '{{ $lead->previous_quote_policy_number }}';

        if (quoteTypeId == quoteTypeCar) {
            // Car Quote: display calendar on next_followup_date
            $('#next_followup_date').daterangepicker({
                timePicker: true,
                singleDatePicker: true,
                timePicker24Hour: true,
                locale: {
                    format: 'DD-MM-YYYY HH:mm:ss',
                },
            });
            // Car Quote: conditionally hide lead_status options
            if (!isLeadPoolRole) {
                $("#leadStatus option[value='9']").hide(); // Fake
                $("#leadStatus option[value='35']").hide(); // Duplicate
            }
            if (!isPaRole) {
                $("#leadStatus option[value='15']").hide(); // Transaction Approved
            }
            // Car Quote: Lead_Status 'Lost' viewable only for renewal lead
            if ($.trim(renewal_batch) != '' || $.trim(previous_quote_policy_number) != '' || source == '{{ LeadSourceEnum::RENEWAL_UPLOAD }}') {
                $("#leadStatus option[value='17']").show(); // Lost
                $("#lostReason").show(); // Lost Reason
            } else {
                $("#leadStatus option[value='17']").hide(); // Lost
                $("#lostReason").hide(); // Lost Reason
            }
            // Car Quote: on page load hideshow next_followup_date conditionally
            var lead_status_code = $('#leadStatus option:selected').val();
            next_followup_date_visibility(lead_status_code);
        }

        $('#lostApprovalStatus').on('change', function() {
            $('.div-approve-reasons, .div-reject-reasons').hide();

            if ($(this).val() == '{{GenericRequestEnum::APPROVED}}') {
                $('.div-approve-reasons').show();
            } else if ($(this).val() == '{{GenericRequestEnum::REJECTED}}') {
                $('.div-reject-reasons').show();
            }
        });

        $('#leadStatus, #lostApprovalStatus').trigger('change');
    });

    function next_followup_date_visibility(lead_status_code) {
        if (lead_status_code == <?php echo QuoteStatusEnum::FollowupCall; ?> ||
            lead_status_code == <?php echo QuoteStatusEnum::Interested; ?> ||
            lead_status_code == <?php echo QuoteStatusEnum::NoAnswer; ?>) {
            $('#quote-next-followup-date').show();
        } else {
            $('#quote-next-followup-date').hide();
        }
        if (lead_status_code == <?php echo QuoteStatusEnum::IMRenewal; ?>) {
            $('#quote-tier').show();
        } else {
            $('#quote-tier').hide();
        }
        return false;
    }
</script>
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Lead Status</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                @php

                    $allowQuoteLogAction = $allowQuoteLogAction ?? true;
                    $carLostChangeStatus = $carLostChangeStatus ?? true;

                    @endphp
                    <form method="POST" action="/quotes/{{$modeltype}}/{{ $lead->id }}/update-lead-status" id="lead-status-form" enctype="multipart/form-data">
                        {{csrf_field()}}
                        <input type="hidden" value="{{$lead->id}}" name="leadId">
                        <input type="hidden" value="{{$modeltype}}" name="modelType">
                        <input type="hidden" value="{{$lead->uuid}}" name="quote_uuid">
                        <input type="hidden" value="{{$lead->advisor_id}}" name="assigned_to_user_id">
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align" for="PREMIUM"><b>Lead Status</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <select @if($lead->quote_status_id == QuoteStatusEnum::TransactionApproved ||
                                        ($quoteTypeId == QuoteTypeId::Car && ($lead->quote_status_id == QuoteStatusEnum::Duplicate || $lead->quote_status_id == QuoteStatusEnum::Fake)
                                        && !auth()->user()->hasAnyRole([RolesEnum::LeadPool, RolesEnum::Admin])) || (!$carLostChangeStatus && !$allowQuoteLogAction)
                                        )
                                        disabled
                                        @endif
                                        class="form-control" id="leadStatus" name="leadStatus">
                                        @foreach ($statuses as $item)
                                        @if($item->id == QuoteStatusEnum::PolicyIssued && isset($isQuoteDocumentEnabled) && $isQuoteDocumentEnabled)
                                        <option @if($status==$item->id) selected="selected" @endif
                                            value="{{$item->id}}">{{$item->text}}</option>
                                        @elseif($item->id != QuoteStatusEnum::PolicyIssued)
                                        <option {{ $item->id == old('leadStatus', $status) ? 'selected' : ''}} value="{{$item->id}}">{{$item->text}}</option>
                                        @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col">

                                @if(isCarLostStatus($lead->quote_status_id))


                                <div class="col-sm-12 mb-3">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Approval Status</b> <span class='required'>*</span></label>
                                    <div class="col-md-6 col-sm-6">
                                        <select @if(!$allowQuoteLogAction) disabled @endif class="form-control" id="lostApprovalStatus" name="lost_approval_status">

                                            @if(!$allowQuoteLogAction)
                                            <option @if(@$carLostQuoteLog->status == GenericRequestEnum::PENDING) selected @endif value="{{ GenericRequestEnum::PENDING }}">{{ GenericRequestEnum::PENDING }}</option>
                                            @endif
                                            <option @if(@$paymentEntityModel->carLostQuoteLog->status == GenericRequestEnum::APPROVED) selected @endif value="{{ GenericRequestEnum::APPROVED }}">{{ GenericRequestEnum::APPROVED }}</option>
                                            <option @if(@$paymentEntityModel->carLostQuoteLog->status == GenericRequestEnum::REJECTED) selected @endif value="{{ GenericRequestEnum::REJECTED }}">{{ GenericRequestEnum::REJECTED }}</option>

                                        </select>
                                    </div>
                                </div>


                                <div class="col-sm-12 mb-3 div-reject-reasons" style="display: none;">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Rejection Reasons</b> <span class='required'>*</span></label>
                                    <div class="col-md-6 col-sm-6">
                                        <select @if(!$allowQuoteLogAction) disabled @endif class="form-control" name="reject_reason_id" @if(!auth()->user()->hasRole(RolesEnum::MarketingOperations))disabled @endif>
                                            @foreach($lostRejectReasons as $lostRejectReason)
                                            <option value="{{$lostRejectReason->id}}" @if(@$paymentEntityModel->carLostQuoteLog->reason_id == $lostRejectReason->id) selected @endif >{{$lostRejectReason->text}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-sm-12 mb-3 div-approve-reasons" style="display: none;">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Approval Reasons</b> <span class='required'>*</span></label>
                                    <div class="col-md-6 col-sm-6">
                                        <select @if(!$allowQuoteLogAction) disabled @endif class="form-control" name="approve_reason_id" @if(!auth()->user()->hasRole(RolesEnum::MarketingOperations))disabled @endif>
                                            @foreach($lostApproveReasons as $lostApproveReason)
                                            <option value="{{$lostApproveReason->id}}" @if(@$paymentEntityModel->carLostQuoteLog->reason_id == $lostApproveReason->id) selected @endif>{{$lostApproveReason->text}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-sm-12 mb-3">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Notes</b> <span class='required'>*</span></label>
                                    <div class="col-md-6 col-sm-6">
                                        <input maxlength="100" value="{{@$paymentEntityModel->carLostQuoteLog->notes }}" @if(!$allowQuoteLogAction) disabled @endif type="text" id="lost_notes" name="lost_notes" class="form-control">
                                    </div>
                                </div>

                                <div class="col-sm-12 mb-3 div-mo-proof-document">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Car Sold / Uncontactable Proof</b></label>
                                    <div class="col-md-6 col-sm-6">
                                        <input @if(!$allowQuoteLogAction) disabled @endif class="form-control hide" type="file" name="mo_proof_document" id="moProofDocument" />
                                    </div>
                                </div>

                                <input type="hidden" name="car_lost_quote_log_id" value="{{@$paymentEntityModel->carLostQuoteLog->id}}">
                                @endif

                                <div id="quote-next-followup-date" style="display: none;">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Followup Date</b> <span class='required'>*</span></label>
                                    <div class="col-md-6 col-sm-6">
                                        <input type="text" id="next_followup_date" name="next_followup_date" value="{{ old('next_followup_date', $lead->next_followup_date) }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please select follow-up date & time">
                                    </div>
                                </div>
                                <div id="quote-tier" style="display: none;">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Tier</b> <span class='required'>*</span></label>
                                    <div class="col-md-6 col-sm-6">
                                        <select id="tier_id" name="tier_id" class="form-control">
                                            <option value="">Please Select Tier</option>
                                            @foreach($tiers as $tier)
                                            <option value="{{$tier->id}}" {{ $tier->id == old('tier_id', $lead->tier_id ?? '') ? 'selected' : ''}}>
                                                {{ $tier->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('tier_id'))
                                        <span class="text-danger">{{ $errors->first('tier_id') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div id="lost-reason-div" style="display: none;">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Lost Reason</b> <span class='required'>*</span></label>
                                    <div class="col-md-6 col-sm-6">
                                        <select class="form-control" id="lostReason" name="lostReason">
                                            <option value="">Select Lost Reason</option>
                                            @foreach ($lostreasons as $item)
                                            <option @if($selectedlostreason==$item->id) selected="selected" @endif
                                                value="{{$item->id}}" >{{$item->text}}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('lostReason'))
                                        <span class="text-danger">{{ $errors->first('lostReason') }}</span>
                                        @endif
                                    </div>
                                    @if($modeltype == quoteTypeCode::Car && isset($selectedlostreason) && ($lostreasons->where('id', $selectedlostreason)->first()?->text == 'Car sold' || $lostreasons->where('id', $selectedlostreason)->first()?->text == 'Uncontactable'))

                                    @endif
                                </div>
                                <div id="trans-div" style="display: none;">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align"><b>TransApp Code</b> <span class='required'>*</span></label>
                                    <div class="col-md-6 col-sm-6">
                                        <input type="text" class="form-control" id="trans_code" name="trans_code" value="">
                                        @if ($errors->has('trans_code'))
                                        <span class="text-danger">{{ $errors->first('trans_code') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Notes</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <textarea @if( ($lead->quote_status_id == QuoteStatusEnum::TransactionApproved) || isCarLostStatus($lead->quote_status_id) )
                                        disabled
                                        @endif class="form-control" id="notes" name="notes"

                                    placeholder="Notes">{{ old('notes', $lead->notes) }}</textarea>
                                </div>
                            </div>
                            <div class="col">

                            </div>
                        </div>

                        <div class="item form-group div-proof-document" style="display: none;">
                            <div class="col">
                                <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Car Sold / Uncontactable Proof</b></label>
                                <div class="col-md-6 col-sm-6">
                                    <input @if(isCarLostStatus($lead->quote_status_id) && !$carLostChangeStatus)
                                    disabled
                                    @endif
                                    class="form-control hide" type="file" name="proof_document" id="proofDocument" />
                                    @if ($errors->has('proof_document'))
                                    <span class="text-danger font-weight-bold" role="alert"><strong>{{ $errors->first('proof_document') }}.</strong></span>
                                    @endif
                                </div>
                            </div>
                            <div class="col">

                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">

                            </div>
                            <div class="col">
                                @cannot(PermissionsEnum::ApprovePayments)
                                <button type="submit" style="float: right;" @if($lead->quote_status_id == QuoteStatusEnum::TransactionApproved
                                    || (!$carLostChangeStatus && !$allowQuoteLogAction)
                                    // || $lead->quote_status_id == QuoteStatusEnum::Lost && isset($lead->lost_approval_status) && $lead->lost_approval_status == GenericRequestEnum::APPROVED && !auth()->user()->hasRole(RolesEnum::MarketingOperations)
                                    // || $lead->quote_status_id == QuoteStatusEnum::Lost && isset($lead->lost_approval_status) && $lead->lost_approval_status == GenericRequestEnum::REJECTED && !auth()->user()->hasRole(RolesEnum::MarketingOperations)
                                    ) disabled @endif class="btn btn-success
                                    btn-sm" id="lead-change-status-btn">Change Status</button>
                                @endcannot
                            </div>
                        </div>
                    </form>


                    @if(isset($paymentEntityModel->carLostQuoteLogs) && $paymentEntityModel->source == LeadSourceEnum::RENEWAL_UPLOAD && auth()->user()->hasAnyRole(RolesEnum::CarAdvisor, RolesEnum::CarManager, RolesEnum::MarketingOperations))
                    <h2>Car Sold / Uncontactable Logs</h2>
                    <div id="lead-history-div">
                        <table id="carLostQuoteLogsTable" class="table table-striped jambo_table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Modified At</th>
                                    <th>Modified By</th>
                                    <th>Notes</th>
                                    <th>Lead Status</th>
                                    <th>Approval Status</th>
                                    <th>Documents</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paymentEntityModel->carLostQuoteLogs as $carLostQuoteLog)
                                <tr>
                                    <td>{{$carLostQuoteLog->created_at}}</td>
                                    <td>
                                        @if(empty($carLostQuoteLog->action_by_id))
                                        {{$carLostQuoteLog->advisor->email}}
                                        @else
                                        Management
                                        @endif
                                    </td>
                                    <td>{{$carLostQuoteLog->notes}}</td>
                                    <td>{{$carLostQuoteLog->quoteStatus->text}}</td>
                                    <td>{{$carLostQuoteLog->status}}</td>
                                    <td>
                                        @foreach($carLostQuoteLog->documents as $document)
                                        <p><a target="_blank" href="{{ createCdnUrl($document->path) }}">Document</a></p>
                                        @endforeach
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
            </div>
        </div>
    </div>

</div>
