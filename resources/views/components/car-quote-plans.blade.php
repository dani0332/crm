<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
$(document).ready(function() {
    $(".editDIV").click(function() {
        $(this).find("span")[0].style.display="none";
        $(this).find("input")[0].style.display="block";
        $(this).find("input")[0].focus();
    });
    $(".editINPUT").blur(function() {
        $(this)[0].style.display="none";
        $(this).prev()[0].innerText=$(this)[0].value;
        $(this).prev().show(300);
    });

    $("input[type='number'][name='discountedPremium[]']").on('input', function() {
        $("#update_discounted_premium").show(300);
    });
});
</script>
<form method="post" action="{{ $uuidModal }}/updateDiscountedPremium" class="form-horizontal form-label-left" role="form"
data-parsley-validate=""novalidate="" autocomplete="off">
    {{csrf_field()}}
    @method('GET')
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Available Plans</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <span class="alert alert-success" id="quotePlansGenerateMsg" style="display: none">Copied</span>
                        <div class="col-auto">
                            <input type="hidden" id="quotePlansGenerateUrl" name="quotePlansGenerateUrl" value="{{ $uuid }}">
                            @if(gettype($listQuotePlans) != 'string')
                                <button type="submit" class="btn btn-primary btn-sm" id="update_discounted_premium" style="display:none;">Update Discounted Premium</button>
                                <button type="button" id="quotePlansGenerateButton" name="quotePlansGenerateButton" class="btn btn-warning btn-sm">Copy link</button>
                            @endif
                        </div>
                    </div>
                    <div id="quote-plans">
                        @if(gettype($listQuotePlans) != 'string')
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
                                            <td>
                                                <input type="hidden" id="quote_plan_id[]" name="quote_plan_id[]" value="{{ $quotePlan->id }}">
                                                <div class="editDIV">
                                                    <span class="editESPAN" style="display:block;text-decoration: underline;">{{ $quotePlan->discountPremium }}</span>
                                                    <input type="number" id="discountedPremium[]" name="discountedPremium[]" value="{{ $quotePlan->discountPremium }}" class="editINPUT" style="display:none;" size="8" maxlength="8">
                                                </div>
                                            </td>
                                            <td>{{ $quotePlan->discountPremium + $quotePlan->vatPremium }}</td>
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
                                        <th>Repair Type</th>
                                        <th>Actual Premium</th>
                                        <th>Discounted Premium</th>
                                        <th>Premium with VAT</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            <tbody>
                                <tr class="odd">
                                    <td valign="top" colspan="7" class="dataTables_empty">{{ ucfirst($listQuotePlans) }}</td>
                                </tr>
                            </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
