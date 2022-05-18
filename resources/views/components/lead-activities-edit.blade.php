

    <form method="post" action="/quotes/updateActivity" autocomplete="off">
        {{ csrf_field() }}
        @method('POST')
        <div class="modal-header">
            <h5 class="modal-title" id="duplicateLeadModalLabel" style="font-size: 16px !important;"><img src="https://i.ibb.co/SRHGR0S/system.png" height="30px" width="30px" />
                <strong style="margin-left: 13px;">Lead Activity</strong>
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="col-md-12" id="followup-div">
                <div class="col">
                    <div class="input-group">
                        <input id="email" type="text" class="form-control" name="title" placeholder="Title" value="{{$record->title}}" />
                        <span class="text-danger"></span>
                    </div>
                </div>
                <div class="col">
                    <div class="input-group">
                        <textarea placeholder="Description" class="form-control" id="description" rows="5" name="description">{{$record->description}}</textarea>
                        <span class="text-danger"></span>
                    </div>
                </div>
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                        <select id="activity-assignee" name="assignee_id" class="form-select form-control"
                            aria-label="Select Assignee">
                            <option selected>Select Assignee</option>
                            @foreach ($advisors as $advisor)
                                <option @if($record->assignee_id == $advisor->id) selected="selected" @endif value="{{ $advisor->id }}">{{ $advisor->name }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger"></span>
                    </div>
                </div>
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
                        <input id="due_date" type="text" class="form-control" name="due_date" value="{{$record->due_date}}" placeholder="Due Date" />
                        <span class="text-danger"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="justify-content: center;">
            <button type="button" onclick="submitUpdateActivity()" class="btn btn-sm btn-success">Update Activity</button>
        </div>
    </form>