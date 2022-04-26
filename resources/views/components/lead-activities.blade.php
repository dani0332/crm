<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Lead Activities</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="modal fade" id="activityEditModal" name="activityEditModal" tabindex="-1" role="dialog"
                    aria-labelledby="activityModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">

                        <div class="modal-content" id="activityEditModalContent">

                        </div>

                    </div>
                </div>
                @php
                    $url = '/quotes/' . strtolower($modeltype) . '/' . $lead->uuid;
                @endphp
                <div id="lead-history-div">
                    <table id="lead-activities" class="table table-striped jambo_table" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Title</th>
                                <th style="width: 10%;">CDBID</th>
                                <th style="width: 20%;">Client Name</th>
                                <th style="width: 10%;">Followup Date</th>
                                <th style="width: 20%;">Assigned To</th>
                                <th style="width: 10%;">Done</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                            @foreach ($activities as $activity)
                            @php
                             $quotetypename = '';
                             $token =$activity['quote_type_id'];
                                switch ($token) {
                                    case 1:
                                        $quotetypename = 'car';
                                        break;
                                    case 2:
                                        $quotetypename = 'home';
                                        break;
                                    case 3:
                                        $quotetypename = 'health';
                                        break;
                                    case 4:
                                        $quotetypename = 'life';
                                        break;
                                    case 5:
                                        $quotetypename = 'business';
                                        break;
                                    case 6:
                                        $quotetypename = 'bike';
                                        break;
                                    case 7:
                                        $quotetypename = 'yacht';
                                        break;
                                    case 8:
                                        $quotetypename = 'travel';
                                        break;
                                }
                            @endphp
                                <tr>
                                    <td>{{ $activity['title'] }}</td>
                                    <td><a href="{{ $url }}">{{ $activity['quote_request_id'] }}</a></td>
                                    <td>{{ $activity['client_name'] }}</td>
                                    <td>{{ $activity['due_date'] }}</td>
                                    <td>{{ $activity['assignee'] }}</td>
                                    <td>
                                        <label class="custom-checkbox"><input type="checkbox"
                                                @if ($activity['status'] == 1) checked="checked" @endif
                                                class="activityChk1" name="activityChk" value="{{ $activity['id'] }}">
                                            <span class="checkbox"></span>
                                        </label>
                                    </td>
                                    <td><button id="activity-edit-btn" data-type="{{$quotetypename}}" data-quote-uuid="{{ $activity['quote_uuid'] }}" data-record-id="{{ $activity['id'] }}" class="btn btn-sm btn-warning" onclick="activityEdit1(this)">Edit</button>
                                        <button id="activity-edit-btn" class="btn btn-sm btn-warning" data-type="{{$quotetypename}}" data-quote-uuid="{{ $activity['quote_uuid'] }}" data-record-id="{{ $activity['id'] }}" onclick="deleteActivity1(this)">Delete</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
