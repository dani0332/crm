@extends('layouts.app_livewire')
@section('title','Advisor Conversion Report')
@section('content')
  <h1 class="text-3xl text-center mb-14 text-[#308BCA]">
    Advisor Conversion Report
  </h1>
  @livewire('advisor-conversion-report-table')
@endsection
