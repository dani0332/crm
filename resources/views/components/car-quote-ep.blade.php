@php
use App\Enums\PaymentStatusEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
$websiteURL = config('constants.AFIA_WEBSITE_DOMAIN');
@endphp
<div class="row">
  <div class="col-md-12 col-sm-12">
    <div class="x_panel">
      <div class="x_title">
        <h2>Embedded Products</h2>
        <div class="clearfix"></div>
      </div>
      <div class="x_content">
        <div class="row">
          <div class="col-auto mr-auto"></div>
          <span class="alert alert-success" id="quotePlansGenerateMsg" style="display: none">Copied</span>
          <div class="col-auto">
           
          </div>
        </div>
        

        <table id="dataTableCarEmbeddedProducts" class="table table-striped jambo_table datatable-car-embedded-products" style="width:100%">
          <thead>
            <tr>
              <th> <input type="checkbox" id="flowcheckall_ep" value="" /></th>
              <th>Product Reference ID</th>
              <th>Product / Service</th>
              <th>Price with VAT</th>
              <th>EP Status</th>
              <th>Last Updated Date</th>
              <th>Payment Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          @foreach ($transactions as $key => $transaction)
							@if(!isset($transaction->id))
								@continue;
							@endif
            <tr>
              <td>
                <input type="checkbox" class="car_ep_checkbox" name="toggle_plans_checkbox" value="" />
              </td>
              <td>{{ $transaction->code}}</td>
              <td>{{ $transaction->product->embeddedProduct->display_name }}</td>
              <td>{{ $transaction->price_with_vat }}</td>
              <td></td>
              <td>{{ $transaction->updated_at->format('d-m-Y h:m:s') }}</td>
              <td>{{ $transaction->paymentStatus->text }}</td>
              <td>
                <div>
                <button class="btn btn-success btn-sm">
                   Send Documents {{ $transaction->product->embeddedProduct->company_documents }}
                </button>
                <button class="btn btn-warning btn-sm">
                Download Certificate
                </button>
                <button class="btn btn-info btn-sm">
                Download Product Wordings
                </button>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
        <span class="alert alert-success" id="payment-link-copy-msg" style="display: none;float:right;position: absolute;z-index: 1;top: -16px;right: 0;">Copied</span>
        
      </div>
    </div>
  </div>
</div>

<script>
$('#flowcheckall_ep').click(function (e) {
  if ($(this).hasClass('checkedAll')) {
    $('.car_ep_checkbox').prop('checked', false);
    $(this).removeClass('checkedAll');
  } else {
    $('.car_ep_checkbox').prop('checked', true);
    $(this).addClass('checkedAll');
  }
});
</script>