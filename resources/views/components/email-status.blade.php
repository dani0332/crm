<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Email Status</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div id="quote-plans">
                    <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Email Address</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th>Create At</th>
                                <th>Updated At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($emailStatuses->count() > 0)
                                @foreach ($emailStatuses as $key => $emailStatus)
                                    <tr>
                                        <td>{{ $emailStatus->id }}</td>
                                        <td>{{ $emailStatus->email_address }}</td>
                                        <td>{{ ucwords($emailStatus->email_status) }}</td>
                                        <td>{{ ucwords($emailStatus->reason) }}</td>
                                        <td>{{ $emailStatus->created_at }}</td>
                                        <td>{{ $emailStatus->updated_at }}</td>
                                    </tr>
                                @endforeach
                            @else
                            <tbody>
                                <tr class="odd">
                                    <td valign="top" colspan="11" class="dataTables_empty">No data available in table</td>
                                </tr>
                            </tbody>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>