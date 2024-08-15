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


                <form id="" method='post' action="{{ url('/quotes/update-last-year-policy') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                    {{csrf_field()}}
                    <input type="hidden" name="modelType" value="{{ strtolower($modeltype) ?? ''}}">
                    <input type="hidden" id="quoteId" name="quoteId" value="{{ $record->id }}">
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" ><b>Renewal Batch#</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">
                            <input type="text" class="form-control" id="renewalBatch" name="renewalBatch"  {{ !empty($record->renewal_batch ) ? 'readonly' : '' }} value="{{ isset($record->renewal_batch) ? $record->renewal_batch : '' }}" required >
                            </p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" ><b>Previous Policy Number</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ isset($record->previous_quote_policy_number) ? $record->previous_quote_policy_number : '' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Previous Policy Expiry Date</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ isset($record->previous_policy_expiry_date) ? $record->previous_policy_expiry_date : '' }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Previous Policy Price</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ isset($record->previous_quote_policy_premium) ? $record->previous_quote_policy_premium : '' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Previous Policy Start Date</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ isset($record->policy_start_date) ? $record->policy_start_date : '' }}</p>

                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" ><b>Previous Advisor</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ isset($record->previous_advisor_id_text) ? $record->previous_advisor_id_text : '' }}</p>
                            </div>
                        </div>
                    </div>

                    @if(Auth::user()->hasRole(RolesEnum::Admin))
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Previous Import Code</b></label>
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
                        <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Policy Number</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->policy_number) ? $record->policy_number : '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Policy Expiry Date</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->policy_expiry_date) ? $record->policy_expiry_date : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align"><b>Lost reason</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($record->lost_reason) ? $record->lost_reason : '' }}</p>
                        </div>
                    </div>
                    <div class="col">

                    </div>
                </div>
                @if (empty($record->renewal_batch ) && auth()->user()->hasRole(RolesEnum::CarManager))
                    <div align="right">
                        <span style="color:red;" id="error-quote-policy-form"></span>
                        <button type="submit" class="btn btn-primary btn-sm" id="">Update</button>
                    </div>
                    @endif
            </form>
            </div>
        </div>
    </div>
</div>
