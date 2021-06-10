@extends('layouts.app')
@section('title','Handler Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Handler Detail</h2>
                 <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('handler.index') }}" class="btn btn-warning btn-sm">Handler List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <form id="demo-form2" method='post'  enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">
                        <b>Name</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $handler->name }}</p>
                    </div>

                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">
                        <b>Created By</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $handler->created_by }}</p>
                    </div>

                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">
                        <b>Updated By</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $handler->updated_by }}</p>
                    </div>

                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">
                        <b>Created At</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $handler->created_at }}</p>
                    </div>

                </div>
                <div class="item form-group">
                    <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">
                        <b>Updated At</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center"> {{ $handler->updated_at }} </p>
                    </div>
                </div>


                <div class="item form-group">
                    <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">
                        <b>Is Active</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center"> {{ $handler->is_active ? 'True' : 'False' }} </p>
                    </div>
                </div>
                <div class="ln_solid"></div>
                <div class="item form-group">
                    <div class="col-md-6 col-sm-6 offset-md-3">
                        <a href="{{ route('handler.edit', ['handler' => $handler->id]) }}" class='btn btn-warning btn-sm'>Edit</a>
                        <a href="#" date-route="{{ route('handler.destroy', ['handler' => $handler->id]) }}"   class='btn btn-warning btn-sm delete'>Delete</a>

                        {{-- <form action="" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class='btn btn-warning btn-sm'>Delete</button>
                        </form> --}}
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning auditablebtn" data-id="{{ $handler->id }}" data-model="App\Models\Handler">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection
