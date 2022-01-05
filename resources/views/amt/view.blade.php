@extends('layouts.app')
@section('title', 'View AMT')
@section('content')
    <div class="row">
        <div class="x_panel">
            <div class="x_title">
                <h2>AMT Leads</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <br />
                <table class="table table-striped jambo_table amt-data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>CDB ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Lead Status</th>
                            <th>Lead Type</th>
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
