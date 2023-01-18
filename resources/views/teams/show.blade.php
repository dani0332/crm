@extends('layouts.app')
@section('title','Team Detail')
@section('content')
@inject('teamService', 'App\Services\TeamService' )
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Team Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('team.index') }}" class="btn btn-warning btn-sm">Team List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('team.update', ['team' => $team->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name"><b> Team Name </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $team->name }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name"><b> Record Type </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $team->type }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email_address"><b> Parent </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $teamService->getTeamNameById($team->parent_team_id) }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="phone_number"><b> Is Active </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $team->is_active }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b> Updated At </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $team->updated_at }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_by"><b> Created by </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $team->created_at }}</p>
                        </div>
                    </div>
                </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <a id="texta" href="{{ route('team.edit', ['team' => $team->id]) }}" class='btn btn-warning btn-sm'>Edit</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
