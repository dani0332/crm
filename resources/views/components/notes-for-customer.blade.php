<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Notes for Customer</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="row">
                    <div class="col-auto mr-auto"></div>
                    <div class="col-auto">
                        <a href="" class="btn btn-primary btn-sm">Create note for customer</a>
                    </div>
                </div>
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
                                    <td>{{ $notesForCustomer->description }}</td>
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