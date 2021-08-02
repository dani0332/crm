@extends('layouts.app')
@section('title','View CarQuote')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-view">
        <div class="x_panel">
            <div class="x_title">
                <h2>Search Car Quotes</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
            <form method="POST" id="search-car-quote" class="form-horizontal form-label-left" role="form" data-parsley-validate=""novalidate="">
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-2 col-sm-2" for="searchtype">Search By</label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" name="searchtype" id="search_type">
                                <option value="id">ID</option>
                                <option value="code">Code</option>
                                <option value="email">Email</option>
                                <option value="mobile_no">Mobile No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-2 col-sm-2" for="searchfield">Search Value</label>
                        <div class="col-md-6 col-sm-6">
                            <div class="input-group">
                                <input type="text" class="form-control" name="searchfield" id="searchfield" placeholder="search ..">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-2 col-sm-2" for="quote-status" id='quotestatus'>Quote Status</label>
                        <div class="col-md-6 col-sm-6">
                            <div class="input-group">
                                <select class="form-control" name="quotestatus" id="quote_status_value">
                                    <option value="">Select</option>
                                    @foreach ($quoteStatuses as $item)
                                    <option value="{{ $item->id }}">{{ $item->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-2 col-sm-2" for="payment-status" id='paymentstatus'>Payment Status</label>
                        <div class="col-md-6 col-sm-6">
                            <div class="input-group">
                                <select class="form-control" name="paymentstatus" id="payment_status_value">
                                    <option value="">Select</option>
                                    @foreach ($paymentStatuses as $item)
                                    <option value="{{ $item->id }}">{{ $item->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">

                    </div>
                    <div class="col">
                        <ul class="nav navbar-right panel_toolbox">
                            <li><input type="submit" class="btn btn-warning btn-sm"></li>
                            <li><input type="reset" class="btn btn-warning btn-sm"></li>
                        </ul>
                    </div>
                </div>
                </form>
                <table class="table table-striped jambo_table carquote-data-table" style="width:100%">
                    <thead>
                        <tr>
                        <th>id</th>
                        <th>Car Value</th>
                        <th>Is Synced</th>
                        <th>Device</th>
                        <th>Code</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        </tr>
                    </thead>

                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
