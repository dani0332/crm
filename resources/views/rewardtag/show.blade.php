@extends('layouts.app')
@section('title','Reward Tag Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reward Tag Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('reward-tags.index') }}" class="btn btn-warning btn-sm">Reward Tag List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('reward-tags.update', ['reward_tag' => $rewardTag->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="text">
                        <b>Text En</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $rewardTag->text }}</p>
                    </div>

                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="text_ar">
                        <b>Text Ar</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $rewardTag->text_ar }}</p>
                    </div>

                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order">
                        <b>Sort Order</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $rewardTag->sort_order }}</p>
                    </div>

                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order">
                        <b> Created At</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $rewardTag->created_at }}</p>
                    </div>

                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order">
                        <b> Updated At</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $rewardTag->updated_at }}</p>
                    </div>

                </div>
                <div class="item form-group">
                    <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">
                        <b>Is Active</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $rewardTag->is_active ? 'True' : 'False' }}</p>
                    </div>
                </div>
                <div class="ln_solid"></div>
                <div class="item form-group">
                    <div class="col-md-6 col-sm-6 offset-md-3">
                        @can('reward-tags-edit')
                        <a href="{{ route('reward-tags.edit', ['reward_tag' => $rewardTag->id])}}"  class='btn btn-warning btn-sm'>Edit</a>
                        @endcan
                        @can('reward-tags-delete')
                        <a href="#" date-route="{{ route('reward-tags.destroy', ['reward_tag' => $rewardTag->id])}}"   class='btn btn-warning btn-sm delete'>Delete</a>
                        {{-- <form action="" method='POST' style="margin-top: -3px;">
                                @csrf
                                @method('DELETE')
                                <button type='submit' class='btn btn-warning btn-sm'>Delete</button>
                        </form> --}}
                        @endcan
                    </div>
                </div>

                </form>
            </div>
        </div>
    </div>
</div>

<div id="auditable">
    <button id='auditablebtn' class="btn btn-warning auditablebtn" data-id="{{ $rewardTag->id }}" data-model="App\Models\RewardTag">
        View Audit Logs
    </button>
</div>

@endsection
