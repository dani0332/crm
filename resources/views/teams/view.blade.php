@extends('layouts.app')
@section('title','View Teams')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Teams</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('generic/team/create') }}" class="btn btn-warning btn-sm">Create Team</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="POST" id="search-teams" class="form-horizontal form-label-left" role="form" data-parsley-validate="" novalidate="" autocomplete="off">
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="searchfield">Team Name</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="name" id="name" placeholder="Search here...">
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <ul class="nav navbar-right panel_toolbox">
                            <li><input type="submit" class="btn btn-warning btn-sm"></li>
                            <li><input type="reset" class="btn btn-warning btn-sm"></li>
                            </ul>
                        </div>
                    </div>
                </form>
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <table class="table table-striped jambo_table teams-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>Id</th>
                          <th>Name</th>
                          <th>Type</th>
                          <th>Parent</th>
                          <th>Created At</th>
                          <th>Updated At</th>
                          <th>Is Active</th>
                        </tr>
                      </thead>

                      <tbody>

                      </tbody>
                    </table>
            </div>
        </div>
    </div>
</div>
@endsection
