@php
    use App\Enums\QuoteStatusEnum;
    use App\Enums\PermissionsEnum;
    use App\Enums\GenericRequestEnum;
    use App\Enums\quoteTypeCode;
    use App\Enums\RolesEnum;
@endphp
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
    $(document).ready(function () {
        var showFollowupStatuses = ['Followed Up','Qualification Pending', 'Quoted', 'FTC Pending', 'FTC Sent', 'Missing Documents Requested', 'Policy Documents Pending', 'Payment Pending', 'Pending with UW', 'Application Pending', 'In Negotiation'];
        if(showFollowupStatuses.find((str) => str == $('#leadStatus option:selected').text())){
            $('#followup-div').show();
        }
        if($('#leadStatus option:selected').text() == 'Lost'){
            $('#lost-reason-div').show();
        }
        else{
            $('#followup-div').hide();
        }
        if($('#trans-div').find('.text-danger').text() != ''){
            $('#trans-div').show();
        }
        if($('#lost-reason-div').find('.text-danger').text() != ''){
            $('#lost-reason-div').show();
        }
        var followupDate = '<?php echo $lead->next_followup_date; ?>';
        if(followupDate != ''){
            $('#followup-date').val(followupDate);
            $('#followup-div').show();
        }
        $("#nextFollowUpDate").daterangepicker({ // TM Leads
            timePicker: true,
            singleDatePicker: true,
            timePicker24Hour: true,
            minDate: followupDate != '' ?
            new Date(followupDate).toLocaleDateString("en-US") :
            new Date().toLocaleDateString("en-US"),
            locale: {
                format: 'YYYY-MM-DD HH:mm:ss'
            }
        });
        $('#leadStatus').on('change', function(){
            if(showFollowupStatuses.find((str) => str == $('#leadStatus option:selected').text())){
                $('#followup-div').show();
            }
            else{
                $('#followup-div').hide();
            }
            if($("#leadStatus option:selected").text() == 'Transaction Approved'){
               $('#trans-div').show();
               $('#lost-reason-div').hide();
            }
            else if($("#leadStatus option:selected").text() == 'Lost'){
                $('#lost-reason-div').show();
                $('#trans-div').hide();
            }
            else{
                $('#lost-reason-div').hide();
                $('#trans-div').hide();
            }
        });

    });
</script>
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Lead Status</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="POST" action="/quotes/{{$modeltype}}/{{ $lead->id }}/UpdateLeadStatus"
                    id="lead-status-form">
                    {{csrf_field()}}
                    <input type="hidden" value="{{$lead->id}}" name="leadId">
                    <input type="hidden" value="{{$modeltype}}" name="modelType">
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="PREMIUM"><b>Lead
                                    Status</b></label>
                            <div class="col-md-6 col-sm-6">
                                <select @if($lead->quote_status_id ==
                                    QuoteStatusEnum::TransactionApproved) disabled @endif class="form-control"
                                    id="leadStatus" name="leadStatus">
                                    <option value="">Select Lead Status</option>
                                    @foreach ($statuses as $item)
                                        @if($item->id == QuoteStatusEnum::PolicyIssued && isset($isQuoteDocumentEnabled) && $isQuoteDocumentEnabled)
                                        <option @if($status==$item->id) selected="selected" @endif
                                            value="{{$item->id}}" >{{$item->text}}</option>
                                        @elseif($item->id != QuoteStatusEnum::PolicyIssued)
                                            <option @if($status==$item->id) selected="selected" @endif
                                                value="{{$item->id}}" >{{$item->text}}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col">
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Notes</b></label>
                            <div class="col-md-6 col-sm-6">
                                <textarea @if($lead->quote_status_id ==
                                    QuoteStatusEnum::TransactionApproved) disabled @endif class="form-control" id="notes" name="notes"
                                    placeholder="Notes">{{$lead->notes ?? ''}}</textarea>
                            </div>
                        </div>
                        <div class="col">
                            <div id="trans-div" style="display: none;">
                                <label class="col-form-label col-md-3 col-sm-3 label-align"><b>TransApp Code</b> <span
                                        class='required'>*</span></label>
                                <div class="col-md-6 col-sm-6">
                                    <input type="text" class="form-control" id="trans_code" name="trans_code" value="">
                                    @if ($errors->has('trans_code'))
                                    <span class="text-danger">{{ $errors->first('trans_code') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div id="lost-reason-div" style="display: none;">
                            <div class="item form-group">
                                <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Lost Reason</b> <span
                                        class='required'>*</span></label>
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
                                </div>
                                @if($modeltype == quoteTypeCode::Car && isset($selectedlostreason) && ($lostreasons->where('id', $selectedlostreason)->first()?->text == 'Car sold' || $lostreasons->where('id', $selectedlostreason)->first()?->text == 'Uncontactable'))
                                <div class="item form-group">
                                <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Approval Status</b> <span
                                        class='required'>*</span></label>
                                    <div class="col-md-6 col-sm-6">
                                        <select class="form-control" name="lost_approval_status" @if(!auth()->user()->hasRole(RolesEnum::MarketingOperations))disabled @endif>
                                                <option @if($lead->lost_approval_status == GenericRequestEnum::PENDING) selected @endif value="{{ GenericRequestEnum::PENDING }}">{{ GenericRequestEnum::PENDING }}</option>
                                                <option @if($lead->lost_approval_status == GenericRequestEnum::APPROVED) selected @endif value="{{ GenericRequestEnum::APPROVED }}">{{ GenericRequestEnum::APPROVED }}</option>
                                                <option @if($lead->lost_approval_status == GenericRequestEnum::REJECTED) selected @endif value="{{ GenericRequestEnum::REJECTED }}">{{ GenericRequestEnum::REJECTED }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="item form-group">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Rejection Reason</b><span
                                        class='required'>*</span></label>
                                    <div class="col-md-6 col-sm-6">
                                        <input class="form-control" type="text" name="lost_approval_reason" value="{{ $lead->lost_approval_reason ?? null }}" @if(!auth()->user()->hasRole(RolesEnum::MarketingOperations) )disabled @endif>
                                        @if ($errors->has('lostReason'))
                                            <span class="text-danger">{{ $errors->first('lostReason') }}</span>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">

                        </div>
                        <div class="col">
                            @cannot(PermissionsEnum::ApprovePayments)
                            <button type="submit" style="float: right;" @if($lead->quote_status_id == QuoteStatusEnum::TransactionApproved 
                                || $lead->quote_status_id == QuoteStatusEnum::Lost && isset($lead->lost_approval_status) && $lead->lost_approval_status == GenericRequestEnum::APPROVED && !auth()->user()->hasRole(RolesEnum::MarketingOperations)
                                || $lead->quote_status_id == QuoteStatusEnum::Lost && isset($lead->lost_approval_status) && $lead->lost_approval_status == GenericRequestEnum::REJECTED && !auth()->user()->hasRole(RolesEnum::MarketingOperations)) disabled @endif class="btn btn-success
                                btn-sm" id="lead-change-status-btn">Change Status</button>
                            @endcannot
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
