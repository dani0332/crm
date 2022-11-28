@extends('layouts.app_livewire')
@section('title','Advisor Distribution Report')
@section('content')
  <h1 class="text-3xl text-center mb-14 text-[#308BCA]">
    Advisor Distribution Report
  </h1>
  @livewire('advisor-distribution-report-table')
@endsection
