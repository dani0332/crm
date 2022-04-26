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
                <form method="POST" action="/quotes/{{$modeltype}}/{{ $lead->id }}/UpdateLeadStatus" id="lead-status-form">
                    {{csrf_field()}}
                    <input type="hidden" value="{{$lead->id}}" name="leadId">
                    <input type="hidden" value="{{$modeltype}}" name="modelType">
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="PREMIUM"><b>Lead Status</b></label>
                            <div class="col-md-6 col-sm-6">
                                <select class="form-control" id="leadStatus" name="leadStatus">
                                    <option value="">Select Lead Status</option>
                                    @foreach ($statuses as $item)
                                        <option
                                            @if($status == $item->id) selected="selected" @endif
                                             value="{{$item->id}}" >{{$item->text}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div id="followup-div" style="display: none">
                                <button class="btn btn-warning btn-sm" style="float:right;width:110px;" type="button" id="add-activity-btn">Add Activity</button>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Notes</b></label>
                            <div class="col-md-6 col-sm-6">
                                <textarea class="form-control" id="notes" name="notes" placeholder="Notes">{{$lead->notes}}</textarea>
                            </div>
                        </div>
                        <div class="col" >
                            <div id="trans-div" style="display: none;">
                                <label class="col-form-label col-md-3 col-sm-3 label-align"><b>TransApp Code</b> <span class='required'>*</span></label>
                                <div class="col-md-6 col-sm-6">
                                    <input type="text" class="form-control" id="trans_code" name="trans_code" value="">
                                    @if ($errors->has('trans_code'))
                                        <span class="text-danger">{{ $errors->first('trans_code') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div id="lost-reason-div" style="display: none;">
                                <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Lost Reason</b> <span class='required'>*</span></label>
                                <div class="col-md-6 col-sm-6">
                                    <select class="form-control" id="lostReason" name="lostReason">
                                        <option value="">Select Lost Reason</option>
                                        @foreach ($lostreasons as $item)
                                            <option @if($selectedlostreason == $item->id) selected="selected" @endif value="{{$item->id}}" >{{$item->text}}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('lostReason'))
                                        <span class="text-danger">{{ $errors->first('lostReason') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">

                        </div>
                        <div class="col">
                            <button type="submit" style="float: right;" class="btn btn-success btn-sm" id="lead-change-status-btn">Change Status</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
