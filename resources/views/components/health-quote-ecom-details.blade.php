<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>E-COM Details</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PLAN NAME"><b>PLAN NAME</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $data['planName'] ? ucwords($data['planName']): '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PROVIDER NAME"><b>PROVIDER NAME</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $data['providerName'] ? $data['providerName']: '' }}</p>
                        </div>
                    </div>
                    
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAYMENT STATUS"><b>PAYMENT STATUS</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $data['paymentStatus'] ? $data['paymentStatus']: '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAID AT"><b>PAID AT</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $data['paidAt'] ? $data['paidAt']: '' }}</p>
                        </div>
                    </div>
                    
                </div>
                <div class="item form-group">
                <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Network"><b>Network</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $data['network'] ? ucwords($data['network']): '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                      
                        <div class="col-md-6 col-sm-6">
                        
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
