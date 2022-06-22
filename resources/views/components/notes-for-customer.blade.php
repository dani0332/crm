<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Notes for Customer</h2>
                <button class="btn btn-sm btn-warning" style="float:right;width:170px;" type="button" id="send-note-for-customer-btn">Send Notes to Customer</button>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Description</th>
                            <th>Created At</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($notesForCustomers->count() > 0)
                            @foreach ($notesForCustomers as $key => $notesForCustomer)
                                <tr>
                                    <td>{{ $notesForCustomer->id }}</td>
                                    <td>@php echo nl2br(htmlentities(str_replace("<br />", "", $notesForCustomer->description))) @endphp</td>
                                    <td>{{ $notesForCustomer->created_at }}</td>
                                    <td>{{ $notesForCustomer->createdby ? $notesForCustomer->createdby->name : '' }}</td>
                                </tr>
                            @endforeach
                        @else
                        <tbody>
                            <tr class="odd">
                                <td valign="top" colspan="4" class="dataTables_empty">No data available in table</td>
                            </tr>
                        </tbody>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>