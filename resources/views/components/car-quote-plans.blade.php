<form method="post" action="#" class="form-horizontal form-label-left" role="form" data-parsley-validate=""novalidate="" autocomplete="off">
    {{csrf_field()}}
    @method('GET')
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Available Plans</h2>
                    <div class="clearfix"></div>
                </div>
                @if(gettype($listQuotePlans) != 'string')
                <div class="x_content">
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <span class="alert alert-success" id="quotePlansGenerateMsg" style="display: none">Copied</span>
                        <div class="col-auto">
                            <input type="hidden" id="quotePlansGenerateUrl" name="quotePlansGenerateUrl" value="{{ $uuid }}">
                            <button type="button" id="quotePlansGenerateButton" name="quotePlansGenerateButton" class="btn btn-warning btn-sm">Copy link</button>
                        </div>
                    </div>
                    <div id="quote-plans">
                        <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Provider Name</th>
                                    <th>Plan Name</th>
                                    <th>Repair Type</th>
                                    <th>Actual Premium</th>
                                    <th>Discounted Premium</th>
                                    <th>Premium with VAT</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($listQuotePlans as $key => $quotePlan)
                                    <tr>
                                        <td>{{ ucwords($quotePlan->providerName) }}</td>
                                        <td>{{ ucwords($quotePlan->name) }}</td>
                                        <td>{{ $quotePlan->repairType }}</td>
                                        <td>{{ $quotePlan->actualPremium }}</td>
                                        <td>{{ $quotePlan->discountPremium }}</td>
                                        <td>{{ $quotePlan->discountPremium + $quotePlan->vatPremium }}</td>
                                        <td><a href="#" planDetailUrl="{{ $uuidModal }}/plan_details/{{ $quotePlan->id }}"
                                            data-toggle="modal" data-target="#quotePlanModal" class="quotePlanModalPopup">View</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <h4>{{ $listQuotePlans }}</h4>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>
