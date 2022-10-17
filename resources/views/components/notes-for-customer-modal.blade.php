
<script>
    $(document).ready(function () {
        $("#send-notes-to-customer-btn").click(function() {
            var txtDescription= $("#txtDescription").val();
            if($.trim(txtDescription).length == 0) {
                $('#send-notes-to-customer-validation-msg').text('Please enter notes.').css('color', 'red');
                $('#send-notes-to-customer-btn').show();
                return false;
            } else {
                $('#send-notes-to-customer-validation-msg').text('Sending...').css('color', '#73879C');
                $('#send-notes-to-customer-btn').hide();
                return true;
            }
        });
    });
</script> 

<div class="modal fade" id="notesForCustomerModal" name="notesForCustomerModal" tabindex="-1" role="dialog"
        aria-labelledby="notesForCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <form method="post" action="{{ url('/quotes/car/addNoteForCustomer') }}" autocomplete="off">
                    {{ csrf_field() }}
                    @method('POST')
                    <input type="hidden" value="{{ $record->id }}" id="quote_id" name="quote_id">
                    <input type="hidden" value="{{ $quoteTypeId }}" id="quote_type_id" name="quote_type_id">
                    <input type="hidden" value="{{ $record->uuid }}" id="quote_uuid" name="quote_uuid">
                    <input type="hidden" value="{{ $record->first_name }} {{ $record->last_name }}" name="customer_name" name="customer_name">
                    <input type="hidden" value="{{ $record->email }}" id="customer_email" name="customer_email">
                    <input type="hidden" value="{{ $record->code }}" id="quote_cdb_id" name="quote_cdb_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notesForCustomerModalLabel" style="font-size: 16px !important;"> <i class="fa fa-cog" aria-hidden="true"></i>
                            <strong style="margin-left: 13px;">New Note for Customer</strong> 
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="col-md-12">
                        <div class="col">
                            <div class="input-group">
                                <textarea id="txtDescription" name="description" class="form-control" placeholder="Type here..." maxlength="500" rows="10"></textarea>                                </div>
                                <small class="text-muted">Max allowed 500 characters</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="justify-content: center;">
                    <table style="text-align: center;">
                        <tr><td><div style="color:red !important;font-size: 12px;">(Note: Once added it cannot be edited or deleted.)</div></td></tr>
                        <tr><td><div><button type="submit" class="btn btn-sm btn-warning" id="send-notes-to-customer-btn">Send Note</button></div></td></tr>
                        <tr><td><div id="send-notes-to-customer-validation-msg"></div></td></tr>
                    </table>
                    </div>
                </form>
            </div>
        </div>
    </div>