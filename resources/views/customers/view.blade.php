@extends('layouts.app')
@section('title','View Customer')
@section('content')
<div class="row">
    <div class="x_panel">
        <div class="x_title">
            <h2>Search Customers</h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">
        <form method="POST" id="search-customer" class="form-horizontal form-label-left"  role="form"  data-parsley-validate=""novalidate="">
            <div class="item form-group">
                <label class="col-form-label col-md-2 col-sm-2 " for="searchtype">Search By
                </label>
                <div class="col-md-6 col-sm-6 ">
                    <select class="form-control" name="searchtype" id="search_type">
                        <option value="email">Email</option>
                    </select>
                </div>
            </div>

            <div class="item form-group">
                <label class="col-form-label col-md-2 col-sm-2 " for="searchfield">Search Value
                </label>
                <div class="col-md-6 col-sm-6 ">
                    <div class="input-group">
                        <input type="text" class="form-control" name="searchfield" id="searchfield" placeholder="search ..">
                    </div>
                </div>
            </div>
            <div class="item form-group">
                <label class="col-form-label col-md-2 col-sm-2 " for="first-name">
                </label>
                <div class="col-md-6 col-sm-6 ">
                    <div class="input-group">
                        <button type="submit" class="btn btn-warning">Search</button>
                    </div>
                </div>
            </div>

        </form>
        </div>
    </div>
        <div class="x_panel">
            <div class="x_title">
                <h2>Customers</h2>

                {{-- <ul class="nav navbar-right panel_toolbox">
                    <li><button id="resubmit_api_carquote" class="btn btn-success btn-sm">ReSubmit Api</button></li>
                </ul> --}}
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                {{-- <div id="success_message" class="alert alert-success" style="display:none"></div>
                <div id="error_message" class="alert alert-danger" style="display:none"></div> --}}
                <br />


                <br/>
                <table  class="table table-striped jambo_table customer-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>id</th>
                          <th>Name</th>
                          <th>Email</th>
                          <th>Mobile No</th>
                          <th>Gender</th>
                          <th>Has Alfred Access</th>
                          <th>DOB</th>
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
