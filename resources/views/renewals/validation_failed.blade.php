@extends('layouts.app_livewire')
@section('title','Validation Failed Detail')
@section('content')
<div>
    <div class="flex flex-wrap gap-2 justify-between items-center">
        <h2 class="text-lg font-bold">
            Validation Failed Detail
        </h2>
        <div class="flex gap-2">
            <a type="_blank" href="{{ request()->url() }}/download" class="btn-3">Download Excel File</a>
            <a href="{{ url('renewals/uploaded-leads') }}" class="btn-2">Batches List</a>
        </div>
    </div>

    @if(session()->has('success'))
    <div class="alert alert-success">{{ session()->get('success') }}</div>
    @endif
    @if(session()->has('message'))
    <div class="alert alert-danger">{{ session()->get('message') }}</div>
    @endif

    @livewire('renewal-validation-failed-table', ['batch_id' => $batch_id])

</div>
@endsection