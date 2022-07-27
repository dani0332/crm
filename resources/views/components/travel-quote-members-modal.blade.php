<div class="modal fade" id="addTravelMemberModal" name="addTravelMemberModal" tabindex="-1" role="dialog"
        aria-labelledby="activityModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content" id="member_model_content_form">
                    <form method="post" action="{{ route('travelers.store') }}" autocomplete="off">
                        {{ csrf_field() }}
                        <input type="hidden" name="travel_quote_request_id" value="{{$id}}" id="">
                        <input type="hidden" name="modelType" value="Travel" id="">
                        <div class="modal-header">
                            <h5 class="modal-title" id="duplicateLeadModalLabel" style="font-size: 16px !important;">
                                <i class="fa fa-users" aria-hidden="true"></i>
                                <strong style="margin-left: 13px;">New Members</strong>
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="col-md-12">
                                <div class="col-md-6">
                                    <div class="input-group">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align">Name</label>
                                        <input type="text" readonly name="" id="" class="form-control" title="" value="Member"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align">DOB</label>
                                        <input type="date" name="dob" id="" class="form-control" title="DOB"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer" style="justify-content: center;">
                            <button type="submit" class="btn btn-sm btn-success">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>