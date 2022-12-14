@extends('layouts.app_livewire')
@section('title','Uploaded Renewal Leads Files')
@section('content')
<div>
    <h2 class="text-lg font-bold mb-4">
        Uploaded Renewal Leads Files
    </h2>

    @livewire('uploaded-renewal-leads-table')

</div>
@endsection