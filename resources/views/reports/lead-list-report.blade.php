@extends('layouts.app_livewire')
@section('title','Lead List Report')
@section('content')
  <h1 class="text-3xl text-center mb-14 text-[#308BCA]">
    Lead List Report
  </h1>
  @livewire('lead-list-report-table')
@endsection
