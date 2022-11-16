@extends('layouts.app_live')
@section('title','Advisor Performance Report')
@section('content')
<h1 style="font-size: 33px;text-align: center;color: #308BCA;margin-bottom: 60px;">Advisor Performance Report</h1>
@livewire('advisor-performance-report-table')
@endsection
