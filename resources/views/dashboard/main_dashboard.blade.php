@extends('layouts.app_live')
@section('title','Dashboard')
@section('content')
<div class="">
    <div class="row">
        <div class="p-6 m-20 bg-white rounded shadow">
            {!! $chart->container() !!}
        </div>
    </div>
</div>
@endsection
