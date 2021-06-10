@extends('layouts.app')
@section('title','Reward Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reward Detail </h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('reward.index') }}" class="btn btn-warning btn-sm">Rewards List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('reward.update', ['reward' => $reward->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="coupon_code">
                            <b>Coupon Code</b>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $reward->coupon_code }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">
                            <b>Partner</b>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $reward->partner ? $reward->partner->name : "" }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="discount">
                            <b>Discount</b>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $reward->discount }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="start_date">
                            <b> Start Date </b>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $reward->start_date }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="end_date">
                            <b>End Date</b>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $reward->end_date }}</p>
                        </div>
                    </div>
                    {{-- <div class="item form-group">
                        <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">Is Flat Discount</label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $reward->is_flat_discount ? 'True' : 'False' }}</p>
                        </div>
                    </div> --}}
                    <div class="item form-group">
                    <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">
                        <b>Reward Category</b>
                    </label>
                        <div class="col-md-6 col-sm-6">
                            @foreach($reward->rewardCategories as $rewardCategory)
                                <button type="button" class="btn btn-disabled">{{ $rewardCategory->text }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="item form-group">
                    <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">
                        <b>Reward Tag</b>
                    </label>
                        <div class="col-md-6 col-sm-6">
                            @foreach($reward->rewardTags as $rewardTag)
                                <button type="button" class="btn btn-disabled">{{ $rewardTag->text }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="item form-group">
                        <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">
                            <b>Is Active</b>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $reward->is_active ? 'True' : 'False' }}</p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>

                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                        @can('rewards-edit')
                        <a href="{{ route('reward.edit', ['reward' => $reward->id])}}" class='btn btn-warning btn-sm'>Edit</i></a>
                        @endcan
                        @can('rewards-delete')
                        <a href="#" date-route="{{ route('reward.destroy', ['reward' => $reward->id])}}" class='btn btn-warning btn-sm delete'>Delete</a>
                        @endcan
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reward Translations</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('reward.reward-translation.create',['reward'=>$reward->id]) }}" class="btn btn-warning btn-sm">Create Reward Translation</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                    <thead>
                      <tr>
                        <th>id</th>
                        <th>Title</th>
                        <th>Product Image</th>
                        <th>Language</th>
                      </tr>
                    </thead>


                    <tbody>
                      @foreach($reward->rewardTranslations as $key => $rewardTranslation)
                      <tr>
                        <td><a href="{{ route('reward.reward-translation.show', ['reward'=>$reward->id,'reward_translation' => $rewardTranslation->id]) }}">
                        {{ $rewardTranslation->id }}</a></td>
                        <td>{{ $rewardTranslation->title }}</td>
                        <td><img src="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$rewardTranslation->product_image }}" style='width:40px;'/></td>
                        <td>{{ $rewardTranslation->lang }}</td>

                      </tr>
                      @endforeach
                    </tbody>
                  </table>
            </div>
        </div>
    </div>
</div>

@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $reward->id }}" data-model="App\Models\Reward">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection
