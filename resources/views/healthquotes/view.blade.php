@extends('layouts.app')
@section('title','View Health Quote')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Health Quotes</h2>

                {{-- <ul class="nav navbar-right panel_toolbox">
                    <li><button id="resubmit_api_healthquote" class="btn btn-success btn-sm">ReSubmit Api</button></li>
                </ul> --}}
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div id="success_message" class="alert alert-success" style="display:none"></div>
                <div id="error_message" class="alert alert-danger" style="display:none"></div>
                <br />
                <table  class="table table-striped jambo_table healthquote-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>id</th>
                          <th>Preference</th>
                          <th>Is Synced</th>
                          <th>Device</th>
                          <th>Code</th>
                          <th>Created At</th>
                          <th>Updated At</th>
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
