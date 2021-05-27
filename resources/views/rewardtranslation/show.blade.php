@extends('layouts.app')
@section('title','Reward Translation Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reward Translation Detail</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <table class="table table-striped">
                    <tr>
                        <td>
                            Title 
                        </td>
                        <td>
                            {{ $rewardTranslation->id }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Description 1
                        </td>
                        <td>
                            {!! $rewardTranslation->description1 !!}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Description 2
                        </td>
                        <td>
                            {!! $rewardTranslation->description2 !!}
                        </td>
                    </tr>
                    <tr>
                        <td>
                           Instructions
                        </td>
                        <td>
                            {!! $rewardTranslation->instructions !!}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Product Image
                        </td>
                        <td>
                            <img src="{{  \Config::get('constants.azure_storage_url').'myrewards/'.$rewardTranslation->product_image }}" style='width:200px;'/> <hr />
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Full Width Banner
                        </td>
                        <td>
                            <img src="{{  \Config::get('constants.azure_storage_url').'myrewards/'.$rewardTranslation->full_width_banner_image }}" style='width:200px;'/> <hr />
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Generic Banner Image
                        </td>
                        <td>
                            <img src="{{  \Config::get('constants.azure_storage_url').'myrewards/'.$rewardTranslation->generic_banner_image }}" style='width:200px;'/> <hr />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Language
                        </td>
                        <td>
                            {{ $rewardTranslation->lang }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Terms And Conditions
                        </td>
                        <td>
                            {{ $rewardTranslation->terms_and_conditions }}
                        </td>
                    </tr>

                    <tr>    
                    <td >
                        <a href="{{ route('reward.reward-translation.edit', ['reward'=>$reward->id,'reward_translation' => $rewardTranslation->id]) }}" class='btn btn-info btn-sm'><i class="fa fa-edit"></i> </a>
                    </td>
                    <td >
                        
                        <form action="{{ route('reward.reward-translation.destroy', ['reward'=>$reward->id,'reward_translation' => $rewardTranslation->id]) }}" method="POST">  
                            @csrf 
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>
                        </form>
                        
                    </td>
                    </tr>
                </table >
                    
                </div>
            </div>
        </div>
    </div>
</div>


<x-auditable  :auditableId="$rewardTranslation->id" auditableType="App\Models\RewardTranslation"/>
@endsection