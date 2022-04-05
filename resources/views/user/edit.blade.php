@extends('layouts.app')
@section('title','Edit User')
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<style>
    .select2-results__option[aria-selected=true] {
        display: none;
    }
</style>
<script>
    function loadManagers(teamId, manager_id){
        $.ajax({
                url: '/getTeamManagers?teamId=' + teamId,
                type: "get",
                success: function(response) {
                    $('#user-manager-select').find('option').remove().end().append('<option value="0" selected="selected">None</option>');
                    for (let index = 0; index < response.length; index++) {
                        const element = response[index];
                        if(manager_id == element.id){
                            $('#user-manager-select').append('<option value="'+element.id+'" selected="selected">'+element.name+'</option>');
                        }
                        else{
                        $('#user-manager-select').append($("<option></option>").attr("value", element.id).text(element.name));
                        }
                    }
                    $('.select-manager').removeAttr('disabled');
                    $(".loader").hide();
                },
            });
    }
    function loadAdditionalTeams(additionalTeams){
        $("#additionalTeams-select").empty();
        additionalTeams.forEach(element => {
            var select  = $("#additionalTeams-select");
            var parentTeamId = $("#user-team-select").val();
            if(parseInt(element.id) !== parseInt(parentTeamId)){
                select.append('<option value="'+element.id+'">'+element.name+'</option>');
            }
        });
    }
    function loadSubTeams(team_id, previous_sub_team_id){
        $(".loader").show();
        $.ajax({
                url: '/getSubTeams?teamId=' + team_id,
                type: "get",
                success: function(response) {
                    $('#sub-team').find('option').remove().end().append('<option value="0" selected="selected">None</option>');
                    for (let index = 0; index < response.length; index++) {
                        const element = response[index];
                        $('#sub-team').append($("<option></option>").attr("value", element.id).text(element.name));
                    }
                    if(previous_sub_team_id != 0){
                        $('#sub-team').val(previous_sub_team_id);
                    }
                    $(".loader").hide();
                },
            });
    }
    $(document).ready(function(){
        var additionalTeams = JSON.parse('<?php echo json_encode($teams); ?>');
        var previous_sub_team_id = JSON.parse('<?php echo json_encode("$user->sub_team_id"); ?>');
        var previous_selected_additional_teams = JSON.parse('<?php echo json_encode("$user->additional_team_ids"); ?>');
        var previous_selected_manager = JSON.parse('<?php echo json_encode("$user->manager_id"); ?>');
        var previous_selected_teamId = JSON.parse('<?php echo json_encode("$user->team_id"); ?>');
        loadAdditionalTeams(additionalTeams);
        $('#additionalTeams-select').select2({
            placeholder: 'Select teams for MyLeads Tab visiblity',
            width: '100%',
            allowClear: true
        });
        loadManagers(previous_selected_teamId, previous_selected_manager);
        $('#additionalTeams-select').val(previous_selected_additional_teams.split(',')).trigger('change');
        $("#user-team-select").on('change', function(){
            $(".loader").show();
            loadAdditionalTeams(additionalTeams);
            loadSubTeams($(this).val(), previous_sub_team_id);
            
            loadManagers($(this).val(), previous_selected_manager);
            $(".loader").hide();
        });
    });

</script>
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit User</h2>
                 <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('users.index') }}" class="btn btn-warning btn-sm">Users List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('users.update', ['user' => $user->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Name <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="name" name="name" value="{{ $user->name }}" class="form-control ">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email">Email <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="email" id="email" name="email" value="{{ $user->email }}" class="form-control">
                            @if ($errors->has('email'))
                                <span class="text-danger">{{ $errors->first('email') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="mobile_no">Mobile Number </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="mobile_no" name="mobile_no" value="{{ $user->mobile_no }}" class="form-control">
                            @if ($errors->has('mobile_no'))
                                <span class="text-danger">{{ $errors->first('mobile_no') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="landline_no">Landline Number </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="landline_no" name="landline_no" value="{{ $user->landline_no }}" class="form-control">
                            @if ($errors->has('landline_no'))
                                <span class="text-danger">{{ $errors->first('landline_no') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="password">Password</label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="password" id="password" name="password" value="{{ $user->password }}" class="form-control">
                            @if ($errors->has('password'))
                                <span class="text-danger">{{ $errors->first('password') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Roles <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <select name="roles[]" multiple="multiple" style="margin-bottom:15px;"
                                            class="form-control select2 select-roles">
                            @foreach(array_chunk($roles, 6) as $chunk)
                                @foreach($chunk as $skey=>$item)
                                <option value="{{$item }}" @if(in_array($item, $userRole)) selected="selected" @endif>
                                {{ $item }}
                                </option>
                                @endforeach
                            @endforeach
                        </select>
                            @if ($errors->has('roles'))
                                <span class="text-danger">{{ $errors->first('roles') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Main Team<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <select name="team" id=user-team-select class="form-control select2">
                                @foreach ($teams as $team)
                                <option value="{{ $team->id }}" @if ($team->id == $selectedTeam) selected="selected" @endif>{{ $team->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('team'))
                                <span class="text-danger">{{ $errors->first('team') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Sub-teams</label>
                        <div class="col-md-6 col-sm-6 ">
                            <select name="sub_team_id" id="sub-team" class="form-control">
                                <option @if($user->sub_team_id == null) selected="selected" @endif value="0">None</option>
                                @foreach ($subTeams as $team)

                                @if ($user->sub_team_id == $team->id)
                                <option value="{{ $team->id }}" selected>{{ $team->name }}</option>
                                @else
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">LOB Visibility</label> 
                        <div class="col-md-6 col-sm-6 ">
                            <select name="additionalTeams[]" id="additionalTeams-select" class="form-control select2 " multiple="multiple">
                                @foreach ($teams as $team)
                                <option value="{{ $team->id }}" @if(str_contains($selectedAdditionalTeams, $team->id)) selected="selected" @endif>{{ $team->name }}</option>
                                @endforeach
                            </select>
                            
                        </div>
                        <div class="col-md-3 col-sm-3">
                            <i class="fa fa-info-circle" id="tooltipGm" style="margin-top: 15px;" title="Additional teams selection helps advisor see leads from selected teams as well" ></i>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Manager</label>
                        <div class="col-md-6 col-sm-6 ">
                            <select name="manager_id" id=user-manager-select class="form-control">
                                <option @if(count($managers) == 0) selected="selected" @endif value="0">None</option>
                                @foreach ($managers as $manager)
                                    <option value="{{ $manager['id'] }}" {{ $manager['id'] == old('manager_id', $user->manager_id) ? 'selected' : '' }}>
                                        {{ $manager['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($errors->has('manager_id'))
                                <span class="text-danger">{{ $errors->first('manager_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm">Update & Continue Updating</button> <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" >Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
