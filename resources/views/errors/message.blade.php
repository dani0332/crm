@extends('layouts.app')
@section('title','Error Notification')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Hey!</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
             <p>{{$message}}</p>
            </div>
        </div>
    </div>
</div>
@endsection
