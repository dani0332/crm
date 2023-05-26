<div class="modal fade" id="activityModal" name="activityModal" tabindex="-1" role="dialog"
        aria-labelledby="activityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">

            <div class="modal-content">
                <form method="post" action="/activities/create-activity" autocomplete="off">
                    {{ csrf_field() }}
                    @method('POST')
                    <input type="hidden" value="{{ strtolower($modeltype) }}" name="modelType">
                    <input type="hidden" value="{{ strtolower($modeltype) }}" name="parentType">
                    <input type="hidden" value="{{ strtolower($record->id) }}" name="entityId">
                    <input type="hidden" value="{{ strtolower($record->code) }}" name="entityCode">
                    <input type="hidden" value="{{ $record->uuid }}" name="entityUId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="duplicateLeadModalLabel" style="font-size: 16px !important;"> <i class="fa fa-cog" aria-hidden="true"></i>
                            <strong style="margin-left: 13px;">New Lead Activity</strong>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="col-md-12">
                            <div class="col">
                                <div class="input-group">
                                    <input id="email" type="text" class="form-control" name="title" placeholder="Title" />
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group">
                                    <textarea placeholder="Description" class="form-control" id="description" rows="5" name="description"></textarea>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                    <select id="activity-assignee" name="assignee_id" class="form-select form-control"
                                        aria-label="Select Assignee" required>
                                        <option value="" selected>Select Assignee</option>
                                        @foreach ($advisors as $advisor)
                                            <option @if(Auth::user()->id == $advisor->id) selected="selected" @endif value="{{ $advisor->id }}">{{ $advisor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
                                    <input id="due_date" type="text" class="form-control" name="due_date"
                                        placeholder="Due Date" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="justify-content: center;">
                        <button type="submit" class="btn btn-sm btn-success">Add Activity</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
