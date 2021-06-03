@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="">
    <div class="row" >
    <div class="x_panel transparent">
          <div class="x_title">
            <h2>Dashboard</h2>
            <div class="filter">
              <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                <i class="fa fa-calendar"></i>
                <span>April 29, 2021 - May 28, 2021</span> <b class="caret"></b>
              </div>
            </div>
            <div class="clearfix"></div>
          </div>
          <div class="x_content">
            <x-dashboard-tile
            icon="fa fa-comments-user"
            class='customer'
            title='Customers' />

            <x-dashboard-tile
            icon="fa fa-comments-o"
            class='carquote'
            title='Car Quotes' />

          </div>
        </div>
    </div>
  </div>
@endsection
