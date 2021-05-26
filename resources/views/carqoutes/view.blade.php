@extends('layouts.app')
@section('title','View CarQoute')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Search Car Qoutes</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
            <form method="POST" id="search-car-qoute" class="form-horizontal form-label-left"  role="form"  data-parsley-validate=""novalidate="">
                <div class="item form-group">
                    <label class="col-form-label col-md-2 col-sm-2 " for="first-name">Search By 
                    </label>
                    <div class="col-md-9 col-sm-9 ">
                        ID:
                        <input type="radio" class="flat" name="searchtype" checked  value="id"  required />
                        Code:
                        <input type="radio" class="flat" name="searchtype"  value="code" />

                        Email:
                        <input type="radio" class="flat" name="searchtype"  value="email"   />
                        Mobile No:
                        <input type="radio" class="flat" name="searchtype"  value="mn" />


                        Name:
                        <input type="radio" class="flat" name="searchtype"  value="name"   />
                        Code:
                        <input type="radio" class="flat" name="searchtype"  value="code" />
                    </div>
                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-2 col-sm-2 " for="first-name">Search Value 
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <div class="input-group">
                            <input type="text" class="form-control" name="searchfield" id="searchfield" placeholder="search ..">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-warning">Search!</button>
                            </span>
                        </div>
                    </div>
                </div>
                {{-- <div class="row">
                    <div class="col-md-2">Search By</div>
                    <div class="col-md-10">
                        <div class="form-group">
                            <p>
                                
                            </p>
                        </div>
                        <br />
                    </div>
                    <div class="col-md-2">Search Value</div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <input type="text" class="form-control" name="searchfield" id="searchfield" placeholder="search ..">
                        </div>                       
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                        <button type="submit" class="btn btn-warning">Search</button>
                        </div>
                    </div>
                </div> --}}
            </form>
            </div>
        </div>
        <div class="x_panel">
            <div class="x_title">
                <h2>Car Qoutes</h2>
                
                {{-- <ul class="nav navbar-right panel_toolbox">
                    <li><button id="resubmit_api_carqoute" class="btn btn-success btn-sm">ReSubmit Api</button></li>
                </ul> --}}
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                {{-- <div id="success_message" class="alert alert-success" style="display:none"></div>
                <div id="error_message" class="alert alert-danger" style="display:none"></div> --}}
                <br />
                
                
                <br/>
                <table  class="table table-striped jambo_table carqoute-data-table" style="width:100%">
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