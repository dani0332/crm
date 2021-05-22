@extends('layouts.app')
@section('title','View Partner')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Partners</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('rewards/partner/create') }}" class="btn btn-success btn-sm">Create Partner</a></li>
                </ul>
                <div class="clearfix"></div>
                
            </div>
            <div class="x_content">
                <br />
                <table  class="table table-striped table-bordered data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>id</th>
                          <th>Name</th>
                          <th>Name Ar</th>
                          <th>Logo</th>
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