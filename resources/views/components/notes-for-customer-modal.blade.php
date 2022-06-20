<div class="modal fade" id="notesForCustomerModal" name="notesForCustomerModal" tabindex="-1" role="dialog"
        aria-labelledby="notesForCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <form method="post" action="{{ url('/quotes/car/addNoteForCustomer') }}" autocomplete="off">
                    {{ csrf_field() }}
                    @method('POST')
                    <input type="hidden" value="{{ $record->id }}" name="quote_id">
                    <input type="hidden" value="{{ $quoteTypeId }}" name="quote_type_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notesForCustomerModalLabel" style="font-size: 16px !important;"> <i class="fa fa-cog" aria-hidden="true"></i>
                            <strong style="margin-left: 13px;">New note for customer</strong>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="col-md-12" id="followup-div">
                            <div class="col">
                                <div class="input-group">
                                    <input type="text" id="description" name="description" class="form-control" placeholder="Type here..." maxlength="255" required />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="justify-content: center;">
                        <button type="submit" class="btn btn-sm btn-success">Add Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>