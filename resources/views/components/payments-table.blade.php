<?php
    use App\Enums\PaymentStatusEnum;
    use App\Enums\PermissionsEnum;
    ?>
<script>
    $(document).ready(function () {
        $('#add-payment-btn').on('click',function () {
            $('#captured_amount').val('');
            $('#payment_methods').val('');
            $('#collection_type').val('');
            $('#reference').val('');
            $('#payment-reference-div').hide();
            $('#paymentCreateModel').modal('show');
        });
        $('#payment_methods').on('change',function () {
            $(this).val() != 'CC' ? $('#payment-reference-div').show() : $('#payment-reference-div').hide();
        });

        $('#upayment-reference-div, #payment-reference-div').hide();

        $('#create-payment-btn').on('click',function (e) {
            e.preventDefault();
            if($('#captured_amount').val() != '' && $('#payment_methods').val() != '' && ($('#payment_methods').val() == 'CC' || ($('#reference').val() != '' && $('#payment_methods').val() != 'CC')) && $('#collection_type').val() != ''){
                $('#create-payment-form').submit();
            }else{
                $('#captured_amount').val() == '' ? $('#captured_amount_validation').show().delay(3000).fadeOut(800) : '';
                $('#captured_amount').val() == '' ? $('#payment_methods_validation').show().delay(3000).fadeOut(800) : '';
                $('#payment_methods').val() != 'CC' && $('#payment_methods').val() != ''  && $('#reference').val() == '' ? $('#reference_validation').show().delay(3000).fadeOut(800) : '';
                $('#collection_type').val() == '' ? $('#collection_type_validation').show().delay(3000).fadeOut(800) : '';
            }
        });

        $('#update-payment-btn').on('click',function (e) {
            e.preventDefault();
            if($('#ucaptured_amount').val() != '' && $('#upayment_methods').val() != '' && ($('#upayment_methods').val() == 'CC' || ($('#ureference').val() != '' && $('#upayment_methods').val() != 'CC')) && $('#ucollection_type').val() != ''){
                $('#update-payment-form').submit();
            }else{
                $('#ucaptured_amount').val() == '' ? $('#ucaptured_amount_validation').show().delay(3000).fadeOut(800) : '';
                $('#upayment_methods').val() == '' ? $('#upayment_methods_validation').show().delay(3000).fadeOut(800) : '';
                $('#upayment_methods').val() != 'CC' && $('#upayment_methods').val() != ''  && $('#ureference').val() == '' ? $('#ureference_validation').show().delay(3000).fadeOut(800) : '';
                $('#ucollection_type').val() == '' ? $('#ucollection_type_validation').show().delay(3000).fadeOut(800) : '';
            }
        });
        $('.edit-payment-btn').on('click',function (e) {
            e.preventDefault();
            $('#ucaptured_amount').val($(this).attr('data-amount'));
            $('#upayment_methods').val($(this).attr('data-payment-method'));
            $('#ureference').val($(this).attr('data-reference'));
            $('#ucollection_type').val($(this).attr('data-collection'));
            $('#uplan_id').val($(this).attr('data-plan'));
            $('#uprovider_id').val($(this).attr('data-provider'));
            $('#ucode').val($(this).attr('data-code'));

            if($(this).attr('data-payment-method') != 'CC'){
                $('#upayment-reference-div').show();
            }else{
                $('#upayment-reference-div').hide();
            }
            $('#paymentUpdateModel').modal('show');
        });

    });
</script>

<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Payments</h2>
                @if($travelPlainModel->plan)
                    @cannot(PermissionsEnum::ApprovePayments)
                        <button class="btn btn-success btn-sm" style="float:right;width:110px;" type="button"
                        id="add-payment-btn">Add Payment</button>
                    @endcannot

                @endif
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div id="lead-history-div">
                    <span class="alert alert-success" id="generateCCLinkMsg"
                        style="display: none;float:right">Copied</span>

                    <table id="datatabless" class="table table-striped jambo_table"
                        style="width:100%;table-layout : fixed">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Payment Status</th>
                                <th>Plan Name</th>
                                <th>Captured Amount</th>
                                <th>Last Status Changed</th>
                                <th>Caputred At</th>
                                <th>Authorized At</th>
                                <th>Payment method</th>
                                <th>Reference</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                            <tr>
                                <td>{{ strtoupper($payment->code) }}</td>
                                <td>{{ $payment->paymentStatus->text }}</td>
                                <td>{{ $travelPlainModel->plan->text }}</td>
                                <td>{{ $payment->captured_amount }}</td>
                                <td>{{ $payment->paymentStatusLogs->first() ? $payment->paymentStatusLogs->first()->created_at :  '' }}</td>
                                <td>{{ $payment->captured_at}}</td>
                                <td>{{ $payment->authorized_at}}</td>
                                <td>{{ $payment->paymentMethod->name }}</td>
                                <td>{{$payment->reference}}</td>
                                <td>
                                    @cannot(PermissionsEnum::ApprovePayments)
                                        <button class="btn btn-sm btn-success generateCCLink">Copy Link</button>
                                        @if($payment->payment_status_id != PaymentStatusEnum::PAID &&
                                        $payment->payment_status_id != PaymentStatusEnum::CAPTURED && $payment->payment_status_id != PaymentStatusEnum::AUTHORISED)
                                            <button class="btn btn-primary btn-sm edit-payment-btn" data-code="{{$payment->code}}"
                                                data-reference="{{$payment->reference}}"
                                                data-amount="{{$payment->captured_amount}}"
                                                data-plan="{{$travelPlainModel->plan->text}}"
                                                data-collection="{{$payment->collection_type}}"
                                                data-payment-method="{{$payment->paymentMethod->code}}"
                                                data-provider="{{$travelPlainModel->plan->insuranceProvider->text}}">Edit</button>
                                        @endif
                                    @endcannot
                                    @can(PermissionsEnum::ApprovePayments)
                                        @if( $payment->paymentMethod->code != 'CC' && $payment->payment_status_id != PaymentStatusEnum::PAID && $payment->payment_status_id != PaymentStatusEnum::CAPTURED)
                                            <button class="btn btn-success btn-sm" id="approve-paymnet-btn" data-code="{{$payment->code}}"
                                                data-reference="{{$payment->reference}}"
                                                data-amount="{{$payment->captured_amount}}"
                                                data-plan="{{$travelPlainModel->plan->text}}"
                                                data-collection="{{$payment->collection_type}}"
                                                data-payment-method="{{$payment->paymentMethod->code}}"
                                                data-provider="{{$travelPlainModel->plan->insuranceProvider->text}}">Approve</button>
                                        @endif
                                    @endcan

                                </td>
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
            <form id="create-payment-form" method="post" action={{url('/payments/'. $modeltype . '/store' )}}
                autocomplete="off">
                @csrf
                <input type="hidden" name="quote_id" value="{{ $travelPlainModel->id }}">
                <input type="hidden" name="modelType" value="{{ $modeltype }}">
                <input type="hidden" name="plan_id" value="{{ $travelPlainModel->plan_id }}">
                <input type="hidden" name="insurance_provider_id"
                    value="{{ $travelPlainModel->plan ? $travelPlainModel->plan->insuranceProvider->id : null }}">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-size: 16px !important;">
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
                                <span class="col-form-label col-md-6 col-sm-6">Capture Amount<span
                                        class="required">*</span></span>
                                <input id="captured_amount" type="number" class="form-control" name="captured_amount"
                                    placeholder="Amount" />
                                <span class="text-danger" style="display: none" id="captured_amount_validation">Please
                                    add capture amount</span>
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Collection Type<span
                                        class="required">*</span></span>
                                <select class="form-control" name="collection_type" id="collection_type">
                                    <option value="">Select Collection Type</option>
                                    <option value="broker">Broker</option>
                                    <option value="insurer">Insurer</option>
                                </select>
                                <span class="text-danger" style="display: none" id="collection_type_validation">Please
                                    select collection type</span>
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Payment Method<span
                                        class="required">*</span></span>
                                <select id='payment_methods' class="form-control" name='payment_methods'>
                                    <option value="">Select Payment Method</option>
                                    @php
                                    $parentPaymentMethods = $paymentMethods->whereNull('parent_code');
                                    $childPaymentMethods = $paymentMethods->whereNotNull('parent_code');
                                    @endphp
                                    @foreach ($parentPaymentMethods as $payment_method)
                                    @if($childPaymentMethods->where('parent_code', $payment_method->code)->count() >
                                    0)
                                    <optgroup label="{{ $payment_method->name }}">
                                        @foreach ($childPaymentMethods->where('parent_code', $payment_method->code)
                                        as
                                        $childPaymentMethod)
                                        <option value="{{ $childPaymentMethod->code }}">{{ $childPaymentMethod->name
                                            }}
                                        </option>
                                        @endforeach
                                    </optgroup>
                                    @else
                                    <option value="{{ $payment_method->code }}">{{ $payment_method->name }}</option>
                                    @endif
                                    @endforeach
                                </select>
                                <span class="text-danger" style="display: none" id="payment_methods_validation">Please
                                    select payment method</span>
                            </div>
                        </div>
                        <br />
                        <div class="item form-group">
                            <div class="col">
                                <div class="input-group">
                                    <label>Provider Name : </label> &nbsp;&nbsp;&nbsp;<b id="provider_id">{{
                                        $travelPlainModel->plan ? $travelPlainModel->plan->insuranceProvider->text :
                                        'Not Found' }}</b>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group">
                                    <label>Plan Name : </label> &nbsp;&nbsp;&nbsp;<b
                                        id="plan_id">{{$travelPlainModel->plan ? $travelPlainModel->plan->text : 'Not
                                        Found'}}</b>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group" class="payment-reference-div" id="payment-reference-div">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Payment Reference<span
                                        class="required">*</span></span>
                                        <input placeholder="Payment Reference" class="form-control"
                                        maxlength="20" id="reference" rows="5" name="reference" />
                                    <span class="text-danger" style="display: none"  id="reference_validation">Please
                                        add
                                        payment reference</span>
                            </div>
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


<div class="modal fade" id="paymentUpdateModel" name="paymentUpdateModel" tabindex="-1" role="dialog"
    aria-labelledby="paymentUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">

        <div class="modal-content">
            <form id="update-payment-form" method="post" action={{url('/payments/'. $modeltype . '/update' )}}
                autocomplete="off">
                @csrf
                <input type="hidden" name="quote_id" value="{{ $travelPlainModel->id }}">
                <input type="hidden" name="modelType" value="{{ $modeltype }}">
                <input type="hidden" name="plan_id" value="{{ $travelPlainModel->plan_id }}">
                <input type="hidden" name="insurance_provider_id"
                    value="{{ $travelPlainModel->plan ? $travelPlainModel->plan->insuranceProvider->id : null }}">
                <input type="hidden" name="paymentCode" id="ucode" value="" />
                <div class="modal-header">
                    <h5 class="modal-title" style="font-size: 16px !important;">
                        <i class="fa fa-cog" aria-hidden="true"></i>
                        <strong style="margin-left: 13px;">Update Payment</strong>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="col-md-12">
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Capture Amount<span
                                        class="required">*</span></span>
                                <input id="ucaptured_amount" type="number" class="form-control" name="captured_amount"
                                    placeholder="Amount" />
                                <span class="text-danger" style="display: none" id="ucaptured_amount_validation">Please
                                    add capture amount</span>
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Collection Type<span
                                        class="required">*</span></span>
                                <select class="form-control" name="collection_type" id="ucollection_type">
                                    <option value="">Select Collection Type</option>
                                    <option value="broker">Broker</option>
                                    <option value="insurer">Insurer</option>
                                </select>
                                <span class="text-danger" style="display: none" id="ucollection_type_validation">Please
                                    select collection type</span>
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Payment Method<span
                                        class="required">*</span></span>
                                <select id='upayment_methods' class="form-control" name='payment_methods'>
                                    <option value="">Select Payment Method</option>
                                    @php
                                    $parentPaymentMethods = $paymentMethods->whereNull('parent_code');
                                    $childPaymentMethods = $paymentMethods->whereNotNull('parent_code');
                                    @endphp
                                    @foreach ($parentPaymentMethods as $payment_method)
                                    @if($childPaymentMethods->where('parent_code', $payment_method->code)->count() >
                                    0)
                                    <optgroup label="{{ $payment_method->name }}">
                                        @foreach ($childPaymentMethods->where('parent_code', $payment_method->code)
                                        as
                                        $childPaymentMethod)
                                        <option value="{{ $childPaymentMethod->code }}">{{ $childPaymentMethod->name
                                            }}
                                        </option>
                                        @endforeach
                                    </optgroup>
                                    @else
                                    <option value="{{ $payment_method->code }}">{{ $payment_method->name }}</option>
                                    @endif
                                    @endforeach
                                </select>
                                <span class="text-danger" style="display: none" id="upayment_methods_validation">Please
                                    select payment method</span>
                            </div>
                        </div>
                        <br />
                        <div class="item form-group">
                            <div class="col">
                                <div class="input-group">
                                    <label>Provider Name : </label> &nbsp;&nbsp;&nbsp;<b id="uprovider_id">{{
                                        $travelPlainModel->plan ? $travelPlainModel->plan->insuranceProvider->text :
                                        'Not Found' }}</b>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group">
                                    <label>Plan Name : </label> &nbsp;&nbsp;&nbsp;<b
                                        id="uplan_id">{{$travelPlainModel->plan ? $travelPlainModel->plan->text : 'Not
                                        Found'}}</b>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group" id="upayment-reference-div" style="display: none;">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Payment Reference<span
                                        class="required">*</span></span>
                                        <input placeholder="Payment Reference" class="form-control"
                                        maxlength="20" id="ureference" rows="5" name="reference" />
                                    <span class="text-danger" style="display: none" id="ureference_validation">Please
                                        add
                                        payment reference</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="justify-content: center;">
                    <button type="button" id="update-payment-btn" class="btn btn-sm btn-success">Update Payment</button>
                </div>
            </form>
        </div>

    </div>
</div>
