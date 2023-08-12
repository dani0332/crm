@php
use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
$url = '/quotes/' . strtolower($modeltype) . '/' . $lead->uuid;
@endphp
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Lead Activities</h2>
                @cannot(PermissionsEnum::ApprovePayments)
                    @if(! auth()->user()->hasRole(RolesEnum::PA))
                        <button class="btn btn-warning btn-sm" style="float:right;width:110px;" type="button" id="add-activity-btn">Add Activity</button>
                    @endif
                @endcannot
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
                <div id="lead-history-div">
                    <table id="lead-activities" class="table table-striped jambo_table" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Title</th>
                                <th style="text-decoration: underline; text-decoration-style: dotted;" data-toggle="tooltip" data-placement="top" title="Reference ID" style="width: 10%;">Ref-ID</th>
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
                             $quoteCode = '';
                             $token =$activity['quote_type_id'];
                                switch ($token) {
                                    case 1:
                                        $quotetypename = 'car';
                                        $quoteCode = 'CAR-';
                                        break;
                                    case 2:
                                        $quotetypename = 'home';
                                        $quoteCode = 'HOM-';
                                        break;
                                    case 3:
                                        $quotetypename = 'health';
                                        $quoteCode = 'HEA-';
                                        break;
                                    case 4:
                                        $quotetypename = 'life';
                                        $quoteCode = 'LIF-';
                                        break;
                                    case 5:
                                        $quotetypename = 'business';
                                        $quoteCode = 'BUS-';
                                        break;
                                    case 6:
                                        $quotetypename = 'bike';
                                        $quoteCode = 'BIK-';
                                        break;
                                    case 7:
                                        $quotetypename = 'yacht';
                                        $quoteCode = 'YAC-';
                                        break;
                                    case 8:
                                        $quotetypename = 'travel';
                                        $quoteCode = 'TRA-';
                                        break;
                                    case 9:
                                        $quotetypename = 'pet';
                                        $quoteCode = 'PET-';
                                        break;
                                    default:
                                        break;
                                }
                            @endphp
                                <tr>
                                    <td>{{ $activity['title'] }}</td>
                                    <td><a href="{{ $url }}">{{ $quoteCode. ''. strtoupper($activity['quote_uuid']) }}</a></td>
                                    <td>{{ $activity['client_name'] }}</td>
                                    <td>{{ $activity['due_date'] }}</td>
                                    <td>{{ $activity['assignee'] }}</td>
                                    <td>
                                        @cannot(PermissionsEnum::ApprovePayments)
                                        <label class="custom-checkbox"><input type="checkbox"
                                                @if ($activity['status'] == 1) checked="checked" @endif
                                                class="activityChk1" name="activityChk" value="{{ $activity['id'] }}">
                                            <span class="checkbox"></span>
                                        </label>
                                        @endcannot
                                    </td>
                                    <td>
                                        @cannot(PermissionsEnum::ApprovePayments)
                                            @if(! auth()->user()->hasRole(RolesEnum::PA))
                                                <button id="activity-edit-btn" data-type="{{$quotetypename}}" data-quote-uuid="{{ $activity['quote_uuid'] }}" data-record-id="{{ $activity['id'] }}" class="btn btn-sm btn-warning" onclick="activityEdit1(this)">Edit</button>
                                                <button id="activity-edit-btn" class="btn btn-sm btn-warning" data-type="{{$quotetypename}}" data-quote-uuid="{{ $activity['quote_uuid'] }}" data-record-id="{{ $activity['id'] }}" onclick="deleteActivity1(this)">Delete</button>
                                            @endif
                                        @endcannot
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
