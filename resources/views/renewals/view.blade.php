@extends('layouts.app')
@section('title','View Customer')
@section('content')
<div class="row">
        <div class="x_panel">
            <div class="x_title">
                <h2>Uploaded Renewal Leads</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table class="table table-striped jambo_table renewals-leads-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>id</th>
                          <th>File Name</th>
                          <th>Total</th>
                          <th>Good</th>
                          <th>Bad</th>
                          <th>Status</th>
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
