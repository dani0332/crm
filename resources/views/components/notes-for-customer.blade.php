@php
use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
@endphp
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Notes for Customer</h2>
                @cannot(PermissionsEnum::ApprovePayments)
                    @if(! auth()->user()->hasRole(RolesEnum::PA))
                        <button class="btn btn-sm btn-warning" style="float:right;width:170px;" type="button" id="send-note-for-customer-btn">Send Notes to Customer</button>
                    @endif
                @endcannot
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
                        @foreach ($notesForCustomers as $key => $notesForCustomer)
                            <tr>
                                <td>{{ $notesForCustomer->id }}</td>
                                <td>@php echo nl2br(htmlentities(str_replace("<br />", "", $notesForCustomer->description))) @endphp</td>
                                <td>{{ $notesForCustomer->created_at }}</td>
                                <td>{{ $notesForCustomer->createdby ? $notesForCustomer->createdby->name : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
