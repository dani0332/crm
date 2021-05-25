@extends('layouts.app')
@section('title','Reward Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reward Detail</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <table class="table table-striped">
                    <tr>
                        <td>
                            Id 
                        </td>
                        <td>
                            {{ $reward->id }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Coupon Code 
                        </td>
                        <td>
                            {{ $reward->coupon_code }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                           Start Date
                        </td>
                        <td>
                            {{ $reward->start_date }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                           End Date
                        </td>
                        <td>
                            {{ $reward->end_date }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Discount
                        </td>
                        <td>
                            {{ $reward->Discount }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Is Flat Discount
                        </td>
                        <td>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" {{ $reward->is_flat_discount ? 'checked' : '' }} class="flat" name='is_flat_discount'>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Is Active
                        </td>
                        <td>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" {{ $reward->is_active ? 'checked' : '' }} class="flat" name='is_active'>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                    @can('rewards-edit')
                    <td >
                        <a href="{{ route('reward.edit', ['reward' => $reward->id]) }}" class='no-style-btn'><i class="fa fa-edit"></i> </a>
                    </td>
                    @endcan
                    @can('rewards-delete')
                    <td >
                        <form action="{{ route('reward.destroy', ['reward' => $reward->id]) }}" method="POST">  
                            @csrf 
                            @method('DELETE')
                            <button type="submit" class="no-style-btn"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                    @endcan
                    </tr>
                </table >
                    
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 col-sm-12 ">
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
                <table id="datatable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                      <tr>
                        <th>Title</th>
                        <th>Product Image</th>
                        <th>Language</th>
                        <th>Actions</th>
                      </tr>
                    </thead>


                    <tbody>
                      
                      @foreach($reward->rewardTranslations as $key => $rewardTranslation)
                      <tr>
                        <td>{{ $rewardTranslation->title }}</td>
                        <td><img src="{{  \Config::get('constants.azure_storage_url').'myrewards/'.$rewardTranslation->product_image }}" style='width:40px;'/></td>
                        <td>{{ $rewardTranslation->lang }}</td>
                        <td>
                        <div class="row">
                            <div class="col-md-1">
                                <a href="{{ route('reward.reward-translation.edit', ['reward'=>$reward->id,'reward_translation' => $rewardTranslation->id]) }}" class='no-style-btn'><i class="fa fa-edit"></i> </a>
                            </div>
                            <div class="col-md-2">
                                <form action="{{ route('reward.reward-translation.destroy', ['reward'=>$reward->id,'reward_translation' => $rewardTranslation->id]) }}" method="POST">  
                                    @csrf 
                                    @method('DELETE')
                                    <button type="submit" class="no-style-btn"><i class="fa fa-trash"></i></button>
                                </form>
                            </div>
                            <div class="col-md-1">
                                <a href="{{ route('reward.reward-translation.show', ['reward'=>$reward->id,'reward_translation' => $rewardTranslation->id]) }}" class='no-style-btn'><i class="fa fa-eye"></i> </a>
                            </div>
                        </div>
                            
                        
                          
                        </td>
                      </tr>
                      @endforeach
                      
                    </tbody>
                  </table>
            </div>
        </div>
    </div>
</div>
@endsection