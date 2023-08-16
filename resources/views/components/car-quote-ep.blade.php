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
        

        <table id="dataTableCarQuotePlans" class="table table-striped jambo_table datatable-car-quote-plans" style="table-layout: fixed;" style="width:100%">
          <thead>
            <tr>
              <th> <input type="checkbox" id="flowcheckall" value="" /></th>
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
            
            
            <tr>
              <td>
                <input type="checkbox" class="car_plans_checkbox" name="toggle_plans_checkbox" value="" />
              </td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
           
          </tbody>
        </table>
        <span class="alert alert-success" id="payment-link-copy-msg" style="display: none;float:right;position: absolute;z-index: 1;top: -16px;right: 0;">Copied</span>
        
      </div>
    </div>
  </div>
</div>