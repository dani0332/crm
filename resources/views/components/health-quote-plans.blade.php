<div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Available Plans</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <span class="alert alert-success" id="quotePlansGenerateMsg" style="display: none">Copied</span>
                    <div class="col-auto">
                        <input type="hidden" id="quotePlansGenerateUrl" name="quotePlansGenerateUrl" value="{{ $ecomHealthInsuranceQuoteUrl }}">
                        @if(gettype($listQuotePlans) != 'string')
                        <button type="button" id="quotePlansGenerateButton" name="quotePlansGenerateButton" class="btn btn-warning btn-sm" style="float: right;">Copy link</button>
                        @endif
                    </div>
                    <div id="quote-plans">
                        @if(gettype($listQuotePlans) != 'string')
                                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Provider Name</th>
                                            <th>Plan Name</th>
                                            <th>Actual Premium with BASMAH</th>
                                            <th>Premium with VAT and BASMAH</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($listQuotePlans as $key => $quotePlan)
                                            <tr>
                                                <td>{{ ucwords($quotePlan->providerName) }}</td>
                                                <td>{{ ucwords($quotePlan->name) }}</td>
                                                <td>{{ $quotePlan->actualPremium + $quotePlan->basmah }}</td>
                                                <td> {{ $quotePlan->actualPremium + $quotePlan->vat + $quotePlan->basmah}}</td>
                                                <td>
                                                    <a href="#" planDetailUrl="{{ $uuidModal }}/plan_details/{{ $quotePlan->id }}"
                                                    data-toggle="modal" data-target="#quotePlanModal" class="btn btn-warning btn-sm quotePlanModalPopup">View</a>
                                                    <button
                                                    class="btn btn-success btn-sm health-plan-link-copy"
                                                    data-planId="{{$quotePlan->id}}"
                                                    data-quoteUUId="{{$record->uuid}}"
                                                    data-providerCode="{{$quotePlan->providerCode}}"
                                                    data-websiteURL="{{config('constants.AFIA_WEBSITE_DOMAIN')}}"
                                                    >Copy</button>
                                                </td>
                                            </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <span class="alert alert-success" id="health-plan-link-copy"
                style="display: none;float:right;position: absolute;z-index: 1;top: -16px;right: 0;">Copied</span>
                            @else
                            <table id="" class="table table-striped jambo_table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Provider Name</th>
                                        <th>Plan Name</th>
                                        <!-- <th>Travel Type</th> -->
                                        <th>Actual Premium</th>
                                        <th>Premium with VAT</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            <tbody>
                                <tr class="odd">
                                    <td valign="top" colspan="11" class="dataTables_empty">{{ ucfirst($listQuotePlans) }}</td>
                                </tr>
                            </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
