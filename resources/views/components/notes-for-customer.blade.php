<meta name="csrf-token" content="{{ csrf_token() }}" />
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $(document).ready(function() {
        $('#send-notes-to-customer-btn').on('click', function(e) {
            e.preventDefault();

            let text = "Do you want to send email to customer?";
            if (confirm(text) == true) {
                    $(".loader").show();
                    $.ajax({
                    url: "{{ url('/quotes/car/sendNotesToCustomer') }}",
                    type: 'post',
                    dataType: "json",
                    contentType: "application/json; charset=utf-8",
                    data: JSON.stringify({
                        quote_id: $('#quote_id').val(),
                        quote_type_id: $('#quote_type_id').val(),
                        quote_uuid: $('#quote_uuid').val(),
                        customer_name: $('#customer_name').val(),
                        customer_email: $('#customer_email').val(),
                        quote_cdb_id: $('#quote_cdb_id').val(),
                        _token: '{{ csrf_token() }}'
                    }),
                    success: function(result) {
                        $('#send-notes-to-customer-response-text').show().text(result).delay(5000).fadeOut(300).addClass('alert alert-success');
                        $(".loader").hide();
                    },
                    error: function(jqXhr, textStatus, errorMessage) {
                        $('#send-notes-to-customer-response-text').show().text('Email has been sent to customer.').delay(5000).fadeOut(300).addClass('alert alert-success');
                        $(".loader").hide();
                    }
                });
            } else {
                return false;
            }

            
        });
    });
</script>
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Notes for Customer</h2>
                <button class="btn btn-primary btn-sm" style="float:right;width:170px;" type="button" id="add-note-for-customer-btn">Add note for customer</button>
                @if($notesForCustomers->count() > 0)
                <input type="hidden" value="{{ $record->id }}" id="quote_id">
                <input type="hidden" value="{{ $quoteTypeId }}" id="quote_type_id">
                <input type="hidden" value="{{ $record->uuid }}" id="quote_uuid">
                <input type="hidden" value="{{ $record->first_name }} {{ $record->last_name }}" id="customer_name">
                <input type="hidden" value="{{ $record->email }}" id="customer_email">
                <input type="hidden" value="{{ $record->code }}" id="quote_cdb_id">
                <button class="btn btn-primary btn-sm" style="float:right;width:170px;" type="button" id="send-notes-to-customer-btn">Send Email to Customer</button>
                <br><br>
                <div class="" id="send-notes-to-customer-response-text" style="display:none"></div>
                @endif
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