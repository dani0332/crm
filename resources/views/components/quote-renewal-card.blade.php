@php
    use App\Enums\RolesEnum;
@endphp
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Last Year's Policy Details</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="renewal_batch"><b>Renewal Batch#</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->renewal_batch) ? $record->renewal_batch : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="previous_quote_policy_number"><b>Previous Policy Number</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->previous_quote_policy_number) ? $record->previous_quote_policy_number : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="previous_policy_expiry_date"><b>Previous Policy Expiry Date</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->previous_policy_expiry_date) ? $record->previous_policy_expiry_date : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="previous_quote_policy_premium"><b>Previous Policy Premium</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->previous_quote_policy_premium) ? $record->previous_quote_policy_premium : '' }}</p>
                        </div>
                    </div>
                </div>
                @if(Auth::user()->hasRole(RolesEnum::Admin))
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="renewal_import_code"><b>Previous Import Code</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ isset($record->renewal_import_code) ? $record->renewal_import_code : '' }}</p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                @endif
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="policy_number"><b>Policy Number</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->policy_number) ? $record->policy_number : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="renewal_expiry_date"><b>Renewal Expiry Date</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->renewal_expiry_date) ? $record->renewal_expiry_date : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="lost_reason"><b>Lost reason</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->lost_reason) ? $record->lost_reason : '' }}</p>
                        </div>
                    </div>
                    <div class="col">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
