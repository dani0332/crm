@extends('layouts.app')
@section('title','View Reward Category')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reward Categories</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('rewards/reward-categories/create') }}" class="btn btn-warning btn-sm">Create Reward Category</a></li>
                </ul>
                <div class="clearfix"></div>
                
            </div>
            <div class="x_content">
                <br />
                <table  class="table table-striped table-bordered reward-category-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>Text</th>
                          <th>Text Ar</th>
                          <th>Sort Order</th>
                          <th>Is Active</th>
                          <th>Created At</th>
                          <th>Updated At</th>
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