<form method="post" action="{{ route('travelers.update', $data->id) }}" autocomplete="off">
    {{ csrf_field() }}
    {{ method_field('PUT') }}
    <input type="hidden" name="travel_quote_request_id" value="{{$data->travel_quote_request_id}}" id="">
    <input type="hidden" name="modelType" value="Travel" id="">
    <div class="modal-header">
        <h5 class="modal-title" id="duplicateLeadModalLabel" style="font-size: 16px !important;">
            <i class="fa fa-users" aria-hidden="true"></i>
            <strong style="margin-left: 13px;">Edit Member</strong>
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
                <input type="text" name="name" id="" readonly class="form-control" title="NAME" value="Member"/>
                   
                </div>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align">DOB</label>
                    <input type="date" name="dob" id="" class="form-control" title="DOB" value="{{$data->dob}}"/>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer" style="justify-content: center;">
        <button type="submit" class="btn btn-sm btn-success">Save</button>
    </div>
</form>