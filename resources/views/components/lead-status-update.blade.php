<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
    $(document).ready(function () {
        var followupDate = '<?php echo $lead->next_followup_date; ?>';
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
                <form method="POST" action="{{ $lead->id }}/UpdateLeadStatus" id="lead-status-form">
                    {{csrf_field()}}
                    <input type="hidden" value="{{$lead->id}}" name="leadId">
                    <input type="hidden" value="car" name="modelType">
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="PREMIUM"><b>Lead Status</b></label>
                            <div class="col-md-6 col-sm-6">
                                <select class="form-control" id="leadStatus" name="leadStatus">
                                    <option value="">Select Lead Status</option>
                                    @foreach ($statuses as $item)
                                        <option @if($status == $item->id) selected="selected" @endif value="{{$item->id}}" >{{$item->text}}</option>
                                    @endforeach
                                </select>

                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Next Follow-up Date</b></label>
                            <div class="col-md-6 col-sm-6">
                                <input type="text" class="form-control" id="nextFollowUpDate" name="nextFollowUpDate" value="{{$lead->next_followup_date}}">
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
                        <div class="col">
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
