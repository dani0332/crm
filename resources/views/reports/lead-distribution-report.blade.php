@extends('layouts.app_livewire')
@section('title','Lead Distribution Report')
@section('content')
  <h1 class="text-3xl text-center mb-14 text-[#308BCA]">
    Lead Distribution Report
  </h1>
  @livewire('lead-distribution-report-table')
@endsection
