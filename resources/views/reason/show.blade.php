@extends('layouts.app')
@section('title','Reason Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reason Detail</h2>
                 <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('reason.index') }}" class="btn btn-warning btn-sm">Reason List</a></li>
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
                        <p class="label-align-center">{{ $reason->name }}</p>
                    </div>

                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Created By
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $reason->created_by }}</p>
                    </div>

                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Modified By
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $reason->updated_by }}</p>
                    </div>

                </div>
                <div class="item form-group">
                    <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">Is Active</label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center"> {{ $reason->is_active ? 'True' : 'False' }} </p>
                    </div>
                </div>
                <div class="ln_solid"></div>
                <div class="item form-group">
                    <div class="col-md-6 col-sm-6 offset-md-3">
                        <a href="{{ route('reason.edit', ['reason' => $reason->id]) }}" class='btn btn-warning btn-sm'>Edit</a>
                        <form action="{{ route('reason.destroy', ['reason' => $reason->id]) }}" method="POST">
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

@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning auditablebtn" data-id="{{ $reason->id }}" data-model="App\Models\Reason">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection
