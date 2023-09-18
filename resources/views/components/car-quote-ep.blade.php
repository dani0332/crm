@php
use App\Enums\PaymentStatusEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
$websiteURL = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
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
              @php
                $pwDoc = json_decode($transaction->company_documents)[0]->path;
                $pwDoc = $pwDoc !== '' ? $websiteURL.$pwDoc: '';
              @endphp
            <tr>
              <td>
                <input type="checkbox" class="car_ep_checkbox" name="toggle_plans_checkbox" value="" />
              </td>
              <td>{{ $transaction->short_code }}-{{ $quoteCode }}</td>
              <td>{{ $transaction->display_name }}</td>
              <td>
                <div>
                @foreach ($transaction->prices as $item)
                    <span class="badge badge-info" style="font-size: 0.75rem;"> {{ ($item->price + ($item->price * 5) / 100) }}</span>
                @endforeach
                </div>
              </td>
              <td><span class="badge badge-light" style="font-size: 0.75rem;">N/A</span></td>
              <td>{{ $transaction->updated_at->format('d-m-Y h:m:s') }}</td>
              <td>
                @if($transaction?->prices[0]?->transactions->first()?->payment_status_id == PaymentStatusEnum::PAID)
                  <span class="badge badge-success" style="font-size: 0.75rem;">Paid</span>
                @elseif($transaction?->prices[0]?->transactions->first()?->payment_status_id == PaymentStatusEnum::PENDING)
                  <span class="badge badge-warning" style="font-size: 0.75rem;">Pending</span>
                @elseif($transaction?->prices[0]?->transactions->first()?->payment_status_id == PaymentStatusEnum::FAILED)
                  <span class="badge badge-danger" style="font-size: 0.75rem;">Failed</span>
                @else
                  <span class="badge badge-light" style="font-size: 0.75rem;">N/A</span>
                @endif

             </td>
              <td>
                <div>
                <button class="btn btn-success btn-sm" disabled>
                   Send Documents
                </button>
                <button class="btn btn-warning btn-sm" disabled>
                Download Certificate
                </button>
                <a href="{{$pwDoc}}" target="_blank" class="btn btn-info btn-sm {{ $pwDoc == '' ? 'disabled': ''}}">
                  Download Product Wordings
                </a>
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