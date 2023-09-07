@php
use App\Enums\RolesEnum;
use App\Enums\PermissionsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentMethodsEnum;
use \App\Enums\quoteTypeCode;

use App\Enums\QuoteTypes;
@endphp
<script>
    var wasSubmitted = false;
    function createPayment()
        {
            if (wasSubmitted) {
                return;
            } else {
                wasSubmitted = true;
                $('#create-payment-btn').text('Creating Payment').prop('disabled', true);
                if($('#captured_amount').val() != '' && $('#payment_methods').val() != '' && ($('#payment_methods').val() == 'CC' || $('#payment_methods').val()== 'IN_PL' || ($('#reference').val() != ''&& $('#payment_methods').val() != 'CC' && $('#payment_methods').val() != 'IN_PL')) && $('#collection_type').val() != ''){
                    $('#create-payment-form').submit();
                    $('#paymentCreateModel').modal('show');
                    wasSubmitted = false;
                }else{
                    $('#captured_amount').val() == '' ? $('#captured_amount_validation').show().delay(3000).fadeOut(800) : '';
                    $('#payment_methods').val() != 'CC' && $('#payment_methods').val() != ''  && $('#reference').val() == '' ? $('#reference_validation').show().delay(3000).fadeOut(800) : '';
                    $('#collection_type').val() == '' ? $('#collection_type_validation').show().delay(3000).fadeOut(800) : '';
                    wasSubmitted = false;
                    $('#create-payment-btn').text('Create Payment').removeAttr('disabled');
                }

            }
        }
    $(document).ready(function () {
        $('#add-payment-btn').on('click',function () {
            $('#captured_amount').val('');
            // $('#payment_methods').val('');
            // $('#collection_type').val('');
            $('#reference').val('');
            $('#payment-reference-div').hide();
            $('#paymentCreateModel').modal('show');
        });
        $('#payment_methods').on('change',function () {
            $(this).val() != 'CC' &&  $(this).val() != 'IN_PL' ? $('#payment-reference-div').show() : $('#payment-reference-div').hide();
        });

        $('#upayment-reference-div, #payment-reference-div').hide();



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
            $('#ureference').val($(this).attr('data-reference'));
            $('#uplan_id').val($(this).attr('data-plan'));
            $('#uprovider_id').val($(this).attr('data-provider'));
            $('#ucode').val($(this).attr('data-code'));
            $('#uinsurance_provider').val($(this).attr('data-insurance_provider_id'));

            $('#upayment-reference-div').hide();

            $('#paymentUpdateModel').modal('show');
        });

        $('#approve-paymnet-btn').on('click', function (e) {
            var code = $(this).attr('data-code');
            var modelType = $(this).attr('data-type');
            var quoteId = $(this).attr('data-quoteId');
            if (confirm('Are you sure you want to approve this payment?')) {
                $.ajax({
                url: '/update-payment-status/',
                method: 'POST',
                data: {
                    _token: $('input[name=_token]').val(),
                    code: code,
                    modelType: modelType,
                    quote_id: quoteId,
                },
                success: function (data) {
                    window.location.reload();
                },
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
                <h2>Payments</h2>
                @if($paymentPlainModel->plan && ! auth()->user()->hasRole(RolesEnum::PA))
                    @cannot(PermissionsEnum::ApprovePayments)
                        @can(PermissionsEnum::PaymentsCreate)
                            <button class="btn btn-success btn-sm" style="float:right;width:110px;" type="button"
                            id="add-payment-btn">Add Payment</button>
                        @endcan
                    @endcannot
                @endif
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div id="lead-history-div">
                    <table id="datatabless" class="table table-striped jambo_table"
                        style="width:100%;table-layout : fixed">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Payment Status</th>
                                <th>Provider Name</th>
                                <th>Plan Name</th>
                                <th>Authorize Amount</th>
                                <th>Status Change Date</th>
                                <th>Authorized At</th>
                                <th>Captured At</th>
                                <th>Payment method</th>
                                <th>Captured Amount</th>
                                <th>Reference</th>
                                <th>Status Detail</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @if(!empty($payments))
                            @foreach ($payments as $payment)
                            <tr>
                                <td>{{ strtoupper($payment->code)}}</td>
                                <td>{{ $payment->paymentStatus->text }}</td>
                                <td>{{ $payment->insuranceProvider->text ?? "" }}</td>
                                <td>{{ !empty($paymentPlainModel->plan)? $paymentPlainModel->plan->text : "" }}</td>
                                <td>{{ $payment->captured_amount }}</td>
                                <td>{{  $payment->paymentStatusLogs->last() != null ? $payment->paymentStatusLogs->last()->created_at : ''}}</td>
                                <td>{{ $payment->authorized_at }}</td>
                                <td>{{ $payment->captured_at }}</td>
                                <td>{{ $payment->paymentMethod->name }}</td>
                                <td>{{ $payment->premium_captured}}</td>
                                <td>{{$payment->reference}}</td>
                                <td>{{$payment->payment_status_message}}</td>
                                <td>
                                    @cannot(PermissionsEnum::ApprovePayments)
                                        @if(($payment->paymentMethod->code == 'CC' ||  $payment->paymentMethod->code == 'IN_PL') && $payment->payment_status_id != PaymentStatusEnum::PAID &&
                                        $payment->payment_status_id != PaymentStatusEnum::CAPTURED && $payment->payment_status_id != PaymentStatusEnum::AUTHORISED &&
                                        ! auth()->user()->hasRole(RolesEnum::PA))
                                            <button
                                            data-modelType="{{$modeltype}}"
                                            data-quoteId="{{$paymentPlainModel->id}}"
                                            data-paymentCode="{{$payment->code}}"
                                            class="btn btn-sm btn-success generateCCLink" style="float: left;">Copy Link</button>
                                        @endif
                                        @if($payment->payment_status_id != PaymentStatusEnum::PAID &&
                                        $payment->payment_status_id != PaymentStatusEnum::CAPTURED && $payment->payment_status_id != PaymentStatusEnum::AUTHORISED &&
                                        ! auth()->user()->hasRole(RolesEnum::PA))
                                         @can(PermissionsEnum::PaymentsEdit)
                                            <button class="btn btn-primary btn-sm edit-payment-btn" data-code="{{$payment->code}}"
                                                data-reference="{{$payment->reference}}"
                                                data-amount="{{$payment->captured_amount}}"
                                                data-plan="{{!empty($paymentPlainModel->plan)? $paymentPlainModel->plan->text : ""}}"
                                                data-insurance_provider_id="{{!empty($payment->insurance_provider_id)? $payment->insurance_provider_id : ""}}"
                                                data-provider="{{ !empty($paymentPlainModel->plan->insuranceProvider)? $paymentPlainModel->plan->insuranceProvider->text :"" }}">Edit</button>
                                            @endcan
                                        @endif
                                    @endcannot
                                    @can(PermissionsEnum::ApprovePayments)
                                        @if( $payment->paymentMethod->code != 'CC' && $payment->payment_status_id != PaymentStatusEnum::PAID && $payment->payment_status_id != PaymentStatusEnum::CAPTURED &&
                                        ! auth()->user()->hasRole(RolesEnum::PA))
                                            <button class="btn btn-success btn-sm" id="approve-paymnet-btn" data-code="{{$payment->code}}"
                                                data-reference="{{$payment->reference}}"
                                                data-amount="{{$payment->captured_amount}}"
                                                data-plan="{{$paymentPlainModel->plan->text}}"
                                                data-collection="{{$payment->collection_type}}"
                                                data-payment-method="{{$payment->paymentMethod->code}}"
                                                data-code="{{$payment->code}}"
                                                data-type="{{$modeltype}}"
                                                data-quoteId="{{$paymentPlainModel->id}}"
                                                data-provider="{{$paymentPlainModel->plan->insuranceProvider->text}}">Approve</button>
                                        @endif
                                    @endcan
                                    @if($payment->payment_status_id == PaymentStatusEnum::PAID)
                                    <button class="btn btn-success btn-sm" disabled >Approved</button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                    <span class="alert alert-success" id="generateCCLinkMsg"
                        style="display: none;float:right;position: absolute;z-index: 1;top: -16px;right: 0;">Copied</span>
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
                <input type="hidden" name="quote_id" value="{{ $paymentPlainModel->id }}">
                <input type="hidden" name="modelType" value="{{ $modeltype }}">
                @if($modeltype == quoteTypeCode::Car || $modeltype == quoteTypeCode::Travel)
                    <input type="hidden" name="plan_id" value="{{ $paymentPlainModel->plan_id }}">
                    <input type="hidden" name="insurance_provider_id"
                           value="{{ $paymentPlainModel->plan ? $paymentPlainModel->plan->insuranceProvider->id : null }}">

                @endif


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
                                <span class="col-form-label col-md-6 col-sm-6">Price Including VAT<span
                                        class="required">*</span></span>
                                <input id="captured_amount" type="number" class="form-control" name="captured_amount"
                                    placeholder="Amount" />
                                <span class="text-danger" style="display: none" id="captured_amount_validation">Please
                                    add capture amount</span>
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Collection Type<span
                                        class="required">*</span></span>
                                <select class="form-control" name="collection_type" id="collection_type" readonly>
                                    <option value="broker" selected="selected">Broker</option>
                                </select>
                                <span class="text-danger" style="display: none" id="collection_type_validation">Please
                                    select collection type</span>
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Payment Method<span
                                        class="required">*</span></span>
                                <select id='payment_methods' class="form-control" name='payment_methods' readonly >
                                    @php
                                    $parentPaymentMethods = $paymentMethods->where('code','=',PaymentMethodsEnum::CreditCard);
                                    @endphp
                                    @foreach ($parentPaymentMethods as $payment_method)
                                        <option value="{{ $payment_method->code }}" selected="selected">{{ $payment_method->name }}</option>
                                    @endforeach
                                </select>
                                <span class="text-danger" style="display: none" id="payment_methods_validation">Please
                                    select payment method</span>
                            </div>
                        </div>

                        <br />
                        @if($modeltype == quoteTypeCode::Car)
                            <div class="item form-group">
                                <div class="col">
                                    <div class="input-group">
                                        <label>Provider Name : </label> &nbsp;&nbsp;&nbsp;<b id="provider_id">{{
                                        $paymentPlainModel->plan ? $paymentPlainModel->plan->insuranceProvider->text :
                                        'Not Found' }}</b>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="input-group">
                                        <label>Plan Name : </label> &nbsp;&nbsp;&nbsp;<b
                                            id="plan_id">{{$paymentPlainModel->plan ? $paymentPlainModel->plan->text : 'Not
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
                        @else
                            <div class="item form-group">
                                <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Provider Name<span
                                        class="required">*</span></span>
                                    <select  class="form-control" name='insurance_provider_id'>
                                        @foreach ($insuranceProviders as $item)
                                            <option value="{{ $item->id }}">{{ $item->text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
                <div class="modal-footer" style="justify-content: center;">
                    @if(! auth()->user()->hasRole(RolesEnum::PA))
                        <button type="button" id="create-payment-btn" onClick="this.disabled=true;createPayment();" class="btn btn-sm btn-success">Create Payment</button>
                    @endif
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
                <input type="hidden" name="quote_id" value="{{ $paymentPlainModel->id }}">
                <input type="hidden" name="modelType" value="{{ $modeltype }}">

                @if($modeltype == quoteTypeCode::Car || $modeltype == quoteTypeCode::Travel)

                    <input type="hidden" name="plan_id" value="{{ $paymentPlainModel->plan_id }}">
                    <input type="hidden" name="insurance_provider_id"
                           value="{{ $paymentPlainModel->plan ? $paymentPlainModel->plan->insuranceProvider->id : null }}">
                @endif
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
                                <span class="col-form-label col-md-6 col-sm-6">Price Including VAT<span
                                        class="required">*</span></span>
                                <input id="ucaptured_amount" type="number" class="form-control" name="captured_amount"
                                    placeholder="Amount" />
                                <span class="text-danger" style="display: none" id="ucaptured_amount_validation">Please
                                    add capture amount</span>
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Collection Type<span
                                        class="required">*</span></span>
                                <select class="form-control" name="collection_type" id="ucollection_type" readonly>
                                    <option value="broker" selected="selected">Broker</option>
                                </select>
                                <span class="text-danger" style="display: none" id="ucollection_type_validation">Please
                                    select collection type</span>
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Payment Method<span
                                        class="required">*</span></span>
                                <select id='upayment_methods' class="form-control" name='payment_methods' readonly>
                                    @php
                                        $parentPaymentMethods = $paymentMethods->where('code','=',PaymentMethodsEnum::CreditCard);
                                    @endphp
                                    @foreach ($parentPaymentMethods as $payment_method)
                                        <option value="{{ $payment_method->code }}" selected="selected">{{ $payment_method->name }}</option>
                                    @endforeach
                                </select>
                                <span class="text-danger" style="display: none" id="upayment_methods_validation">Please
                                    select payment method</span>
                            </div>
                        </div>
                        <br />
                        @if($modeltype == quoteTypeCode::Car || $modeltype == quoteTypeCode::Travel)


                        <div class="item form-group">
                            <div class="col">
                                <div class="input-group">
                                    <label>Provider Name : </label> &nbsp;&nbsp;&nbsp;<b id="uprovider_id">{{
                                        $paymentPlainModel->plan ? $paymentPlainModel->plan->insuranceProvider->text :
                                        'Not Found' }}</b>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group">
                                    <label>Plan Name : </label> &nbsp;&nbsp;&nbsp;<b
                                        id="uplan_id">{{$paymentPlainModel->plan ? $paymentPlainModel->plan->text : 'Not
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
                        @else
                            <div class="item form-group">
                                <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Provider Name<span
                                        class="required">*</span></span>
                                    <select  class="form-control"  id='uinsurance_provider'name='insurance_provider_id'>
                                        @foreach ($insuranceProviders as $item)
                                            <option value="{{ $item->id }}">{{ $item->text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer" style="justify-content: center;">
                    @if(! auth()->user()->hasRole(RolesEnum::PA))
                        <button type="button" id="update-payment-btn" class="btn btn-sm btn-success">Update Payment</button>
                    @endif
                </div>
            </form>
        </div>

    </div>
</div>
