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
            <form method="POST" id="search-car-quote" class="form-horizontal form-label-left"  role="form"  data-parsley-validate=""novalidate="">
                <div class="item form-group">
                    <label class="col-form-label col-md-2 col-sm-2 " for="first-name">Search By
                    </label>
                    <div class="col-md-9 col-sm-9 ">
                        <p>
                        <input type="radio" class="flat" name="searchtype" checked  value="id"  required /> ID
                        </p>
                        <p>
                        <input type="radio" class="flat" name="searchtype"  value="code" /> Code
                        </p>

                        <p>
                        <input type="radio" class="flat" name="searchtype"  value="email"   /> Email
                        </p>
                        <p>
                        <input type="radio" class="flat" name="searchtype"  value="mn" /> Mobile No
                        </p>


                        <p>
                        <input type="radio" class="flat" name="searchtype"  value="name"   /> Name
                        </p>
                        <p>
                        <input type="radio" class="flat" name="searchtype"  value="code" /> Code
                        </p>
                    </div>
                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-2 col-sm-2 " for="first-name">Search Value
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <div class="input-group">
                            <input type="text" class="form-control" name="searchfield" id="searchfield" placeholder="search ..">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-warning">Search</button>
                            </span>
                        </div>
                    </div>
                </div>
            </form>
            </div>
        </div>
        <div class="x_panel">
            <div class="x_title">
                <h2>Car Quotes</h2>

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
                <table  class="table table-striped jambo_table carquote-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>id</th>
                          <th>Car Value</th>
                          <th>Is Synced</th>
                          <th>Device</th>
                          <th>Code</th>
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
@endsection
