@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="">
    <div class="row">
        <div class="x_panel transparent">
            <x-travel-stat-table :statsArray="$statsArray" :headingArray="$headingArray" />
        </div>
    </div>
</div>
@endsection
