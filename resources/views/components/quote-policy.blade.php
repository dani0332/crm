@php
    $dateFormat = config('constants.DATE_FORMAT');
    use App\Enums\QuoteStatusEnum;
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}" />
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('build/js/quote_policy.js') }}"></script>
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Policy Details</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
            <form id="update-quote-policy-form" method='post' action="{{ url('/quotes/'.$quoteType.'/update-quote-policy') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
            {{csrf_field()}}
                <input type="hidden" name="modelType" value="{{ $quoteType ?? ''}}">
                <input type="hidden" id="quote_id" name="quote_id" value="{{ $record->id }}">
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="quote_policy_number"><b>Policy Number</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                        <input type="text" class="form-control" id="quote_policy_number" name="quote_policy_number" value="{{ $record->policy_number }}">
                        </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="quote_policy_issuance_date"><b>Issuance Date</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                        <input type="text" class="form-control" id="quote_policy_issuance_date" name="quote_policy_issuance_date" value="{{ $record->policy_issuance_date ? Carbon\Carbon::parse($record->policy_issuance_date)->format($dateFormat) : '' }}">
                        </p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="quote_policy_start_date"><b>Start Date</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">
                            <input type="text" class="form-control" id="quote_policy_start_date" name="quote_policy_start_date" value="{{ $record->policy_start_date ? Carbon\Carbon::parse($record->policy_start_date)->format($dateFormat) : '' }}">
                            </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="quote_policy_expiry_date"><b>Expiry Date</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">
                            <input type="text" class="form-control" id="quote_policy_expiry_date" name="quote_policy_expiry_date" value="{{ $record->renewal_expiry_date ? Carbon\Carbon::parse($record->renewal_expiry_date)->format($dateFormat) : '' }}">
                            </p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="quote_premium"><b>Premium</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                        <input type="text" class="form-control" id="quote_premium" name="quote_premium" value="{{ $record->premium }}" onkeypress="return isNumberKey(event,this)">
                        </p>
                        </div>
                    </div>
                    <div class="col">

                    </div>
                </div>
                <!-- Transaction Approved -->
                @if($record->quote_status_id == QuoteStatusEnum::TransactionApproved) 
                    <div align="right">
                        <span style="color:red;" id="error-quote-policy-form"></span>
                        <button type="button" class="btn btn-primary btn-sm" id="cancel-quote-policy-btn">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm" id="update-quote-policy-btn">Update</button>
                        <button type="button" class="btn btn-primary btn-sm" id="edit-quote-policy-btn">Edit</button>
                    </div>
                @endif
            </form>
            </div>
        </div>
    </div>
</div>