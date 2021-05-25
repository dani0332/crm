@extends('layouts.app')
@section('title','View CarQoute')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
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
                <form method="POST" id="search-car-qoute" class="form-inline" style="margin-left:16px;" role="form">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <p>
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
                                </p>
                            </div>
                            <hr />
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <input type="text" class="form-control" name="searchfield" id="searchfield" placeholder="search ..">
                            </div>                       
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                            <button type="submit" class="btn btn-primary">Search</button>
                            </div>
                        </div>
                    </div>
                   

                    
                </form>
                <br/>
                <table  class="table table-striped table-bordered carqoute-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th><input type="checkbox" class="multiselect" id="select_all_checkboxes"/></th>
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