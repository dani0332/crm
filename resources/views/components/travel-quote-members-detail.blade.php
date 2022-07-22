<div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Travelers</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div id="quote-plans">
                        @if($members)
                            <table id="" class="table table-striped jambo_table datatable-show" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>DOB</th>
                                        <th>Created At</th>
                                        <th>Updated At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($members as $key => $member)
                                        <tr>
                                            <td>Traveler {{$key+1}}</td>
                                            <td>{{ \Carbon\Carbon::createFromTimestamp(strtotime($member->dob))->format('d-m-Y')}}</td>
                                            <td>{{ $member->created_at }}</td>
                                            <td>{{ $member->updated_at }}</td>
                                            <td><button id="member-details-edit-btn" class="btn btn-sm btn-warning" onclick="editMemberDetail('{{$member->id}}')">Edit</button>
                                            <button id="member-details-delete-btn" class="btn btn-sm btn-warning" onclick="deleteMemberDetail('{{$member->id}}')">Delete</button>
                                            </td>
                                        </tr>
                                @endforeach
                                </tbody>
                            </table>
                            @else
                            <table id="" class="datatable-show table table-striped jambo_table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>DOB</th>
                                        <th>Created At</th>
                                        <th>Updated At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            <tbody>
                                <tr class="odd">
                                    <td valign="top" colspan="11" class="dataTables_empty">No Data Available</td>
                                </tr>
                            </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
