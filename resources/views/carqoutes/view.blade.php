@extends('layouts.app')
@section('title','View CarQoute')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Car Qoutes</h2>
                <div class="clearfix"></div>
                
            </div>
            <div class="x_content">
                <br />
                <table  class="table table-striped table-bordered carqoute-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th><input type="checkbox" class="multiselect" id="select_all_checkboxes"/></th>
                          <th>id</th>
                          <th>Car Value</th>
                          <th>Is Synced</th>
                          <th>Device</th>
                          <th>Code</th>
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