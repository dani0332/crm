@extends('layouts.app_livewire')
@section('title','Advisor Performance Report')
@section('content')
  <h1 class="text-3xl text-center mb-14 text-[#308BCA]">
    Advisor Performance Report
  </h1>
  @livewire('advisor-performance-report-table')
@endsection
