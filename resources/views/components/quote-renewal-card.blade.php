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
                            <p class="label-align-center">{{ isset($record->renewal_batch) ? $record->renewal_batch : NULL }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="previous_quote_policy_number"><b>Previous Policy Number</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->previous_quote_policy_number) ? $record->previous_quote_policy_number : NULL }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="previous_policy_expiry_date"><b>Previous Policy Expiry Date</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->previous_policy_expiry_date) ? $record->previous_policy_expiry_date : NULL }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="previous_quote_policy_premium"><b>Previous Policy Premium</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->previous_quote_policy_premium) ? $record->previous_quote_policy_premium : NULL }}</p>
                        </div>
                    </div>
                </div>
                @if(Auth::user()->hasRole(RolesEnum::Admin))
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="renewal_import_code"><b>Previous Import Code</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ isset($record->renewal_import_code) ? $record->renewal_import_code : NULL }}</p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
