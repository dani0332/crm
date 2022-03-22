<div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Available Plans</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div id="quote-plans">
                        @if(gettype($listQuotePlans) != 'string')
                                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Provider Name</th>
                                            <th>Plan Name</th>
                                            <th>Travel Type</th>
                                            <th>Actual Premium</th>
                                            <th>Premium with VAT</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($listQuotePlans as $key => $quotePlan)
                                            <tr>
                                                <td>{{ ucwords($quotePlan->providerName) }}</td>
                                                <td>{{ ucwords($quotePlan->name) }}</td>
                                                <td>{{ $quotePlan->travelType }}</td>
                                                <td>{{ $quotePlan->actualPremium }}</td>
                                                <td>{{ $quotePlan->vatPremium ? $quotePlan->vatPremium : '' }}</td>
                                                <td><a href="#" planDetailUrl="{{ $uuidModal }}/plan_details/{{ $quotePlan->id }}"
                                                    data-toggle="modal" data-target="#quotePlanModal" class="quotePlanModalPopup">View</a></td>
                                            </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @else
                            <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Provider Name</th>
                                        <th>Plan Name</th>
                                        <th>Travel Type</th>
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
