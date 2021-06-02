@extends('layouts.app')
@section('title','View Transaction')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Transaction</h2>
                @can('role-create')
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('transaction.create') }}" class="btn btn-warning btn-sm">Create Transaction</a></li>
                </ul>
                @endcan

                <div class="clearfix"></div>

            </div>
            <div class="x_content">
                <br />
                <div class="table-responsive">
                <table  class="table table-striped jambo_table transaction-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>Approval Code</th>
                          <th>Transaction Date</th>
                          <th>Insurance Company</th>
                          <th>Premium</th>
                          <th>Risk</th>
                          <th>Transector</th>
                          <th>Handler</th>
                          <th>Mode Of Payment</th>
                          <th>Last Modified</th>
                          <th>Actions</th>
                        </tr>
                      </thead>


                      <tbody>



                      </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection
