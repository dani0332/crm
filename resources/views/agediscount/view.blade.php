@extends('layouts.app')
@section('title', 'View Age Discount')
@section('content')
    <div class="row">
        <div class="x_panel">
            <div class="x_title">
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('discount/age/create') }}" class="btn btn-warning btn-sm">Create Age Discount</a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
        </div>
        <div class="x_panel">
            <div class="x_title">
                <h2>Age Discounts</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <br />
                <table class="table table-striped jambo_table age-discount-data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>id</th>
                            <th>Age Start</th>
                            <th>Age End</th>
                            <th>Discount</th>
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
