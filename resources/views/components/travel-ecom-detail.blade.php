@php
    use App\Enums\PaymentStatusEnum;
@endphp
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>E-COM Details</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PRICE"><b>PRICE</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $travelQuotePremium }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAID AT"><b>PAID AT</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $travelQuotePaidAt }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAYMENT STATUS"><b>PAYMENT STATUS</b></label>
                        <div class="col-md-3 col-sm-3">
                            <p class="label-align-center">{{ $travelQuotePaymentStatus }}</p>
                        </div>
                        @if($record->payment_status_id == PaymentStatusEnum::DECLINED)
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAYMENT STATUS"><b>Reason</b></label>
                            <div class="col-md-3 col-sm-3">
                                <p class="label-align-center">{{ !empty($mainPayment->payment_status_message) ? $mainPayment->payment_status_message : "" }}</p>
                            </div>
                        @endif

                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PROVIDER NAME"><b>PROVIDER NAME</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $travelQuotePlanName }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
