@extends('layouts.app')
@section('title','Reward Tag')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reward Tag Detail</h2>
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
                            {{ $rewardTag->id }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Text 
                        </td>
                        <td>
                            {{ $rewardTag->text }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Text Ar 
                        </td>
                        <td>
                            {{ $rewardTag->text_ar }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Sort Order 
                        </td>
                        <td>
                            {{ $rewardTag->sort_order }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Is Active
                        </td>
                        <td>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" {{ $rewardTag->is_active ? 'checked' : '' }} class="flat" name='is_active'>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        
                    <td >
                        <a href="{{ route('reward-tags.edit', ['reward_tag' => $rewardTag->id]) }}" class='no-style-btn'><i class="fa fa-edit"></i> </a>
                    </td>
                    <td >
                        <form action="{{ route('reward-tags.destroy', ['reward_tag' => $rewardTag->id]) }}" method="POST">  
                            @csrf 
                            @method('DELETE')
                            <button type="submit" class="no-style-btn"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                    </tr>
                </table >
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection