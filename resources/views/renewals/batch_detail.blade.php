@extends('layouts.app_livewire')
@section('title','Email Batch Details')
@section('content')
<div>
    <div class="flex flex-wrap gap-2 justify-between items-center">
        <h2 class="text-lg font-bold">
            Email Batch Details
        </h2>
        <div class="flex gap-2">
            <a href="{{ route('batches-list') }}" class="btn-2">Batches List</a>
            <a class="btn" onclick="return confirm('Do you want to send emails?');" href="{{ route('run-batch-process', $batch) }}">Send Emails</a>
        </div>
    </div>

    @if(session()->has('success'))
    <div class="alert alert-success">{{ session()->get('success') }}</div>
    @endif
    @if(session()->has('message'))
    <div class="alert alert-danger">{{ session()->get('message') }}</div>
    @endif

    @livewire('renewal-email-batch-table', ['batch' => $batch])

</div>
@endsection
