@extends('layouts.app_livewire')
@section('title','Validation Passed Detail')
@section('content')
<div>
    <div class="flex flex-wrap gap-2 justify-between items-center">
        <h2 class="text-lg font-bold">
            Validation Passed Detail
        </h2>
        <a href="{{ route('renewals-uploaded-leads-list') }}" class="btn-2">Batches List</a>
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success">{{ session()->get('success') }}</div>
    @endif
    @if(session()->has('message'))
        <div class="alert alert-danger">{{ session()->get('message') }}</div>
    @endif

    @livewire('renewal-validation-passed-table', ['batch_id' => $batch_id])

</div>
@endsection
