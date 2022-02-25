@extends('layouts.app')
@section('title','Edit User')
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
    $(document).ready(function(){
        $('#additionalTeams-select').select2({
            placeholder: 'Select Additional Teams against user',
            width: '100%',
            allowClear: true
        });
        $("#user-team-select").on('change', function(){
            $(".loader").show();
            $.ajax({
                    url: '/getTeamManagers?teamId=' + this.value,
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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Parent Team <span class="required">*</span></label>
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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Additional Teams</label>
                        <div class="col-md-6 col-sm-6 ">
                            <select name="additionalTeams[]" id="additionalTeams-select" class="form-control select2 " multiple="multiple">
                                @foreach ($teams as $team)
                                <option value="{{ $team->id }}" @if(str_contains($selectedAdditionalTeams, $team->id)) selected="selected" @endif>{{ $team->name }}</option>
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
                            <select name="manager" id=user-manager-select class="form-control select2">
                                <option @if($selectedManager == null) selected="selected" @endif value="0" >None</option>
                                @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}" @if ($manager->id == $selectedManager) selected="selected" @endif>{{ $manager->name }}</option>
                                @endforeach
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
                            <button type="submit" class="btn btn-warning btn-sm">Update & Continue Updating</button> <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" >Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
