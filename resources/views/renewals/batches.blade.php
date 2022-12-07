@extends('layouts.app_livewire')
@section('title','Renewals Batches')
@section('content')
<div>
  <h2 class="text-lg font-bold mb-4">
    Renewals Batches
  </h2>

  @livewire('renewal-batches-table')

</div>
@endsection