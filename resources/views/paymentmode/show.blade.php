@extends('layouts.app')
@section('title','Payment Mode Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Payment Mode Detail</h2>
                 <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('paymentmode.index') }}" class="btn btn-warning btn-sm">Payment Mode List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <form id="demo-form2" method='post'  enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Name
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $paymentmode->name }}</p>
                    </div>

                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Created By
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $paymentmode->created_by }}</p>
                    </div>

                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Modified By
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $paymentmode->updated_by }}</p>
                    </div>

                </div>
                <div class="item form-group">
                    <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">Is Active</label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center"> {{ $paymentmode->is_active ? 'True' : 'False' }} </p>
                    </div>
                </div>
                <div class="ln_solid"></div>
                <div class="item form-group">
                    <div class="col-md-6 col-sm-6 offset-md-3">
                        <a href="{{ route('paymentmode.edit', ['paymentmode' => $paymentmode->id]) }}" class='btn btn-warning btn-sm'>Edit</a>
                        <form action="{{ route('paymentmode.destroy', ['paymentmode' => $paymentmode->id]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class='btn btn-warning btn-sm'>Delete</button>
                        </form>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
