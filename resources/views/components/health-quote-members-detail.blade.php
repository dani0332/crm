<div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Members {{Config::get('constants.KEN_API_ENDPOINT')}}</h2>
                    <button class="btn btn-warning btn-sm" style="float:right;width:110px;" type="button" id="add-edit-health-members-btn">Add Member</button>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div id="quote-plans">
                        @if($members)
                            <table id="" class="table table-striped jambo_table datatable-show" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>DOB</th>
                                        <th>Gender</th>
                                        <th>Salary Band</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($members as $key => $member)
                                        <tr>
                                            <td>Member {{$key+1}}</td>
                                            <td>@if($member->memberCategory){{$member->memberCategory->text}}@endif</td>
                                            <td>{{$member->dob}}</td>
                                            <td>{{$member->gender}}</td>
                                            <td>@if($member->salaryBand){{$member->salaryBand->text}}@endif</td>
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
                                        <th>Category</th>
                                        <th>DOB</th>
                                        <th>Gender</th>
                                        <th>Salary Band</th>
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
