@extends('layouts.app_livewire')
@section('title','Plans Processes')
@section('content')
@php
use App\Enums\RolesEnum;
@endphp
<div>
    <div class="flex flex-wrap gap-2 justify-between items-center">
        <h2 class="text-lg font-bold">
            Plans Processes
        </h2>
        <div class="flex gap-2">
            <a href="{{ route('renewals-batches') }}" class="btn-2">Batches List</a>
            @hasanyrole(RolesEnum::RenewalsManager.'|'.RolesEnum::Admin)
            <a class="btn" onclick="return confirm('Do you want to fetch Plans?');" href="{{ route('batch-fetch-plans', $batch) }}">Fetch Plans</a>
            @endhasanyrole
        </div>
    </div>

    @if(session()->has('success'))
    <div class="alert alert-success">{{ session()->get('success') }}</div>
    @endif
    @if(session()->has('message'))
    <div class="alert alert-danger">{{ session()->get('message') }}</div>
    @endif

    @livewire('renewal-batches-plan-process-table', ['batch' => $batch])

</div>
@endsection
