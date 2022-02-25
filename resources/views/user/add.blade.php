@extends('layouts.app')
@section('title','Add User')
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<style>
    .select2-results__option[aria-selected=true] { display: none;}

    </style>
<script>
    function loadAdditionalTeams(additionalTeams){
        additionalTeams.forEach(element => {
            var select  = $("#additionalTeams-select");
            if(element.id != $("#user-team-select").val()){
                select.append('<option value="'+element.id+'">'+element.name+'</option>');
            }
        });
    }

    function loadManagerByTeam(team_id){
        $(".loader").show();
        $.ajax({
                url: '/getTeamManagers?teamId=' + team_id,
                type: "get",
                success: function(response) {
                    $('#user-manager-select').find('option').remove().end().append('<option value="0" selected="selected">None</option>');
                    for (let index = 0; index < response.length; index++) {
                        const element = response[index];
                        $('#user-manager-select').append($("<option></option>").attr("value", element.id).text(element.name));
                    }
                    $('.select-manager').removeAttr('disabled');
                    $(".loader").hide();
                },
            });
    }

    $(document).ready(function(){
        loadManagerByTeam($("#user-team-select").val());
        $('#additionalTeams-select').select2({
            placeholder: 'Select Additional Teams against user',
            width: '100%',
            allowClear: true
        });
        var additionalTeams = JSON.parse('<?php echo json_encode($teams); ?>');
        loadAdditionalTeams(additionalTeams);
        $("#user-team-select").on('change', function(){
            $("#additionalTeams-select").empty();
            loadAdditionalTeams(additionalTeams);      
            loadManagerByTeam(this.value);
        });
    });

</script>
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create User</h2>
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
                <form id="demo-form2" method='post' action="{{ route('users.store') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Name <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control ">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email">Email Address<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control">
                            @if ($errors->has('email'))
                                <span class="text-danger">{{ $errors->first('email') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="password">Password <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="password" id="password" name="password" value="{{ old('password') }}" class="form-control">
                            @if ($errors->has('password'))
                                <span class="text-danger">{{ $errors->first('password') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Role <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <select name="roles[]" multiple="multiple" style="margin-bottom:15px;"
                                            class="form-control select2 select-roles">
                            @foreach(array_chunk($roles, 6) as $chunk)
                                @foreach($chunk as $skey=>$item)
                                <option value="{{$item }}"}}>
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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Parent Team <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <select name="team" id="user-team-select" class="form-control">
                                @foreach ($teams as $team)

                                @if (old('team') == $team->id)
                                <option value="{{ $team->id }}" selected>{{ $team->name }}</option>
                                @else
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endif
                                @endforeach
                            </select>
                            @if ($errors->has('team'))
                                <span class="text-danger">{{ $errors->first('team') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Additional Teams</label>
                        <div class="col-md-6 col-sm-6 ">
                            <select name="additionalTeams[]" id="additionalTeams-select" class="form-control select2 " multiple="multiple">
                                @foreach ($additionalTeams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('team'))
                                <span class="text-danger">{{ $errors->first('team') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Manager</label>
                            <div class="col-md-6 col-sm-6 ">
                                <select disabled="disabled" id='user-manager-select' name="manager" class="form-control select2 select-manager">
                                    <option value="0" selected="selected" >None</option>
                                </select>
                                @if ($errors->has('manager'))
                                    <span class="text-danger">{{ $errors->first('manager') }}</span>
                                @endif
                            </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm">Create & Add New</button> <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" >Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
