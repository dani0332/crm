@extends('layouts.app_live')
@section('title','AML Download History')
@section('content')

<h2 class="text-lg font-bold mb-4">
  AML Download History
</h2>

@livewire('aml-download-history-table', ['url' => $url])

@endsection