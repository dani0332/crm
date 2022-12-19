@extends('layouts.app_livewire')
@section('title','Car Quotes')
@section('content')
<div>
  <h2 class="text-lg font-bold mb-4">
    Car Quotes
  </h2>

  @livewire('car-quote-table')

</div>
@endsection