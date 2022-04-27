@extends('layouts.app')
@section('title','Uploaded Renewal Leads Files')
@section('content')
<style>
    div.dt-buttons {
        position: relative;
        float: left;
    }
    td {
        word-wrap: break-word;
    }
</style>
<div class="row">
        <div class="x_panel">
            <div class="x_title">
                <h2>Uploaded Renewal Leads Files</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table class="table table-striped jambo_table renewals-leads-data-table" style="table-layout: fixed;" width="100%">
                      <thead>
                        <tr>
                          <th style="width: 40px !important">id</th>
                          <th style="width: 100px !important">File Name</th>
                          <th style="width: 40px !important">Total</th>
                          <th style="width: 40px !important">Good</th>
                          <th style="width: 40px !important">Bad</th>
                          <th style="width: 40px !important">Status</th>
                          <th style="width: 100px !important">Uploaded By</th>
                          <th style="width: 100px !important">Uploaded At</th>
                          <th style="width: 100px !important">Updated At</th>
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
