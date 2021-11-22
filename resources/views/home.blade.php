@extends('layouts.app')
@section('title', 'Home')
@section('content')

<script>
 <?php
   $user = ["email" => Auth::user()->email, "name" => Auth::user()->name, "id" => Auth::user()->id, "role" =>  strtolower(Auth::user()->usersroles[0]->name)];
   $user = json_encode($user);
 ?>
 localStorage.setItem("session", JSON.stringify(<?php echo $user;?>));
 </script>

    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#leadType').on('change', function() {
                var leadType = $(this).val();
                window.location.href = "/leadsearch?leadType=" + leadType;
            });
        });
    </script>
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Search Leads</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="Start Date">Lead Type <span
                                    class="required">*</span></label>
                            <div class="col-md-6 col-sm-6">
                                <select class="form-control" id="leadType" name="leadType">
                                    <option value="">Please Select Lead Type</option>
                                    <option value="home">Home</option>
                                    <option value="health">Health</option>
                                    <option value="life">life</option>
                                    <option value="business">business</option>
                                    <option value="travel">Travel</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
