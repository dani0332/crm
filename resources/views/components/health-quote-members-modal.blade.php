<div class="modal fade" id="addHealthMemberModal" name="addHealthMemberModal" tabindex="-1" role="dialog"
        aria-labelledby="activityModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content" id="member_model_content_form">
                    <form method="post" action="{{ route('members.store') }}" autocomplete="off">
                        {{ csrf_field() }}
                        <input type="hidden" name="health_quote_request_id" value="{{$id}}" id="">
                        <input type="hidden" name="modelType" value="Health" id="">
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
                                    <label class="col-form-label col-md-3 col-sm-3 label-align">Gender</label>
                                        <select name="gender" id="ebp_gender" class="form-control">
                                            <option>Please Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align">DOB</label>
                                        <input type="date" name="dob" id="ebp_dob" class="form-control" title="DOB" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="col-md-6">
                                    <div class="input-group">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align">Category</label>
                                        <select name="member_category" id="ebp_category" class="form-control">
                                            <option>Please Select Member Category</option>
                                            @foreach($categories as $member)
                                            <option value="{{$member->id}}">{{$member->text}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                    <label class="col-form-label col-md-3 col-sm-3 label-align">Salary Band</label>
                                    <select name="salary_band" id="ebp_salary" class="form-control">
                                            <option>Please Select Salary Band</option>
                                            @foreach($salaries as $salary)
                                            <option value="{{$salary->id}}">{{$salary->text}}</option>
                                            @endforeach
                                    </select>
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