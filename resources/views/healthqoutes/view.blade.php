@extends('layouts.app')
@section('title','View Health Qoute')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Health Qoutes</h2>
                
                {{-- <ul class="nav navbar-right panel_toolbox">
                    <li><button id="resubmit_api_healthqoute" class="btn btn-success btn-sm">ReSubmit Api</button></li>
                </ul> --}}
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div id="success_message" class="alert alert-success" style="display:none"></div>
                <div id="error_message" class="alert alert-danger" style="display:none"></div>
                <br />
                <table  class="table table-striped table-bordered healthqoute-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th><input type="checkbox" class="multiselect" id="select_all_checkboxes"/></th>
                          <th>id</th>
                          <th>Preference</th>
                          <th>Is Synced</th>
                          <th>Device</th>
                          <th>Code</th>
                          <th>Actions</th>
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