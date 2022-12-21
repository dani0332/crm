@extends('layouts.app')
@section('title', 'Lead Search')
@section('content')
<table id="failed-jobs-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Connection</th>
            <th>Queue</th>
            <th>Payload</th>
            <th>Exception</th>
        </tr>
    </thead>
</table>

<script>
    $(document).ready(function() {
        $('#failed-jobs-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('failed-jobs.index') }}',
            columns: [
                { data: 'id' },
                { data: 'connection' },
                { data: 'queue' },
                { data: 'payload' },
                { data: 'exception' }
            ]
        });
    });
</script>

@endsection
