@php
use Carbon\Carbon;
@endphp
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Email Status</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Email Subject</th>
                            <th>Email Address</th>
                            <th>Status</th>
                            <th>Reason</th>
                            @if (Auth::user()->hasRole(['ADMIN']))
                            <th>Template Id</th>
                            <th>Customer Id</th>
                            @endif
                            <th>Created At</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($emailStatuses as $key => $emailStatus)
                            <tr>
                                <td>{{ $emailStatus->id }}</td>
                                <td>{{ $emailStatus->email_subject }}</td>
                                <td>{{ $emailStatus->email_address }}</td>
                                <td>{{ ucwords($emailStatus->email_status) }}</td>
                                <td>{{ ucwords($emailStatus->reason) }}</td>
                                @if (Auth::user()->hasRole(['ADMIN']))
                                <td>{{ $emailStatus->template_id }}</td>
                                <td>{{ $emailStatus->customer_id }}</td>
                                @endif
                                <td>{{ isset($emailStatus->created_at) ? Carbon::parse($emailStatus->created_at)->format('d-m-Y H:i:s') : '' }}</td>
                                <td>{{ isset($emailStatus->updated_at) ? Carbon::parse($emailStatus->updated_at)->format('d-m-Y H:i:s') : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>