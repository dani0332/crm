<script>
    $(document).ready(function () {
        $('#add-payment-btn').on('click',function () {
            $('#paymentCreateModel').modal('show');
        });
        $('#payment_methods').on('change',function () {
            var method = $(this).val();
            if(method != 'CC'){
                $('#reference').show();
            }else{
                $('#reference').hide();
            }
        });

        $('#create-payment-btn').on('click',function (e) {
            debugger;
            e.preventDefault();
            if($('#captured_amount').val() != '' && $('#payment_methods').val() != '' && ($('#payment_methods').val() != 'CC' || $('#reference').val() != '') && $('#collection_type').val() != ''){
                $('#create-payment-form').submit();
            }else{
                $('#captured_amount').val() == '' ? $('#captured_amount_validation').show().delay(3000).fadeOut(800) : '';
                $('#payment_methods').val() == '' ? $('#payment_methods_validation').show().delay(3000).fadeOut(800) : '';
                $('#payment_methods').val() != 'CC' && $('#payment_methods').val() != ''  && $('#reference').val() == '' ? $('#reference_validation').show().delay(3000).fadeOut(800) : '';
                $('#collection_type').val() == '' ? $('#collection_type_validation').show().delay(3000).fadeOut(800) : '';
            }
        });

    });
</script>

<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Payments</h2>
                @if($travelPlainModel->plan()->first())
                <button class="btn btn-success btn-sm" style="float:right;width:110px;" type="button" id="add-payment-btn">Add Payment</button>
                @endif
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div id="lead-history-div">
                    <table id="datatabless" class="table table-striped jambo_table" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width: 10%;">Transaction ID</th>
                                <th style="width: 10%;">Payment Status</th>
                                <th style="width: 10%;">Plan Name</th>
                                <th style="width: 10%;">Captured Amount</th>
                                <th style="width: 10%;">Captured date</th>
                                <th style="width: 50%;">Payment method</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td>{{ $payment->code }}</td>
                                    <td>{{ $payment->paymentStatus->text }}</td>
                                    <td>{{ $travelPlainModel->plan->text }}</td>
                                    <td>{{ $payment->captured_amount }}</td>
                                    <td>{{ $payment->captured_at }}</td>
                                    <td>{{ $payment->paymentMethod->code }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentCreateModel" name="paymentCreateModel" tabindex="-1" role="dialog"
aria-labelledby="paymentSalModalLabel" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-lg" role="document">

    <div class="modal-content">
        <form id="create-payment-form" method="post" action={{url('/payments/'. $modeltype . '/store')}} autocomplete="off">
            @csrf
            <input type="hidden" name="quote_id" value="{{ $travelPlainModel->id }}">
            <input type="hidden" name="modelType" value="{{ $modeltype }}">
            <input type="hidden" name="plan_id" value="{{ $travelPlainModel->plan_id }}">
            <input type="hidden" name="insurance_provider_id" value="{{ $travelPlainModel->plan->insuranceProvider->id }}">
            <div class="modal-header">
                <h5 class="modal-title" id="duplicateLeadModalLabel" style="font-size: 16px !important;">
                    <i class="fa fa-cog" aria-hidden="true"></i>
                    <strong style="margin-left: 13px;">New Payment</strong>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="col-md-12">
                    <div class="item form-group">
                        <div class="col">
                            <div class="input-group">
                                <input id="captured_amount" type="number" class="form-control" name="captured_amount" placeholder="Amount" />

                            </div>
                            <span class="text-danger" style="display: none" id="captured_amount_validation">Please add capture amount</span>
                        </div>
                        <div class="col">
                            <div class="input-group">
                                <select class="form-control" name="collection_type" id="collection_type">
                                    <option value="">Select Collection Type</option>
                                    <option value="broker" >Broker</option>
                                    <option value="insurer" >Insurer</option>
                                </select>
                            </div>
                            <span class="text-danger" style="display: none" id="collection_type_validation">Please select collection type</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <select id='payment_methods' class="form-control" name='payment_methods'>
                                <option value="">Select Payment Method</option>
                                @php
                                    $parentPaymentMethods = $paymentMethods->whereNull('parent_code');
                                    $childPaymentMethods = $paymentMethods->whereNotNull('parent_code');
                                @endphp
                                @foreach ($parentPaymentMethods as $payment_method)
                                    @if($childPaymentMethods->where('parent_code', $payment_method->code)->count() > 0)
                                        <optgroup label="{{ $payment_method->name }}">
                                            @foreach ($childPaymentMethods->where('parent_code', $payment_method->code) as $childPaymentMethod)
                                                <option value="{{ $childPaymentMethod->code }}">{{ $childPaymentMethod->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @else
                                        <option value="{{ $payment_method->code }}">{{ $payment_method->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <span class="text-danger" style="display: none" id="payment_methods_validation">Please select payment method</span>
                    </div>
                    <br />
                    <div class="item form-group">
                        <div class="col">
                            <div class="input-group">
                                <label>Provider Name : </label> &nbsp;&nbsp;&nbsp;<b>{{ $travelPlainModel->plan ? $travelPlainModel->plan->insuranceProvider->text : 'Not Found' }}</b>
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group">
                                <label>Plan Name : </label> &nbsp;&nbsp;&nbsp;<b>{{$travelPlainModel->plan ? $travelPlainModel->plan->text : 'Not Found'}}</b>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <textarea style="display: none" placeholder="Payment Reference" class="form-control" id="reference" rows="5" name="reference"></textarea>
                        </div>
                        <span class="text-danger" style="display: none" id="reference_validation">Please add payment reference</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="justify-content: center;">
                <button type="button" id="create-payment-btn" class="btn btn-sm btn-success">Create Payment</button>
            </div>
        </form>
    </div>

</div>
</div>
