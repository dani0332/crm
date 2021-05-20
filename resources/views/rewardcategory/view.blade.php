@extends('layouts.app')
@section('title','View Reward Category')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reward Categories</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('rewards/reward-categories/create') }}" class="btn btn-success btn-sm">Create Reward Category</a></li>
                </ul>
                <div class="clearfix"></div>
                
            </div>
            <div class="x_content">
                <br />
                <table id="datatable" class="table table-striped table-bordered" style="width:100%">
                      <thead>
                        <tr>
                          <th>Text</th>
                          <th>Text Ar</th>
                          <th>Sort Order</th>
                          <th>Is Active</th>
                          <th>Created At</th>
                          <th>Updated At</th>
                          <th>Actions</th>
                        </tr>
                      </thead>


                      <tbody>
                        
                        @foreach($rewardCategories as $key => $rewardCategory)
                        <tr>
                          <td>{{ $rewardCategory->text }}</td>
                          <td>{{ $rewardCategory->text_ar }}</td>
                          <td>{{ $rewardCategory->is_active }}</td>
                          <td>{{ $rewardCategory->sort_order }}</td>
                          <td>{{ $rewardCategory->created_at }}</td>
                          <td>{{ $rewardCategory->updated_at }}</td>
                          <td><a href="{{ route('reward-categories.edit', ['reward_category' => $rewardCategory->id]) }}" class='btn btn-info btn-sm'><i class="fa fa-edit"></i> </a>
                          <form action="{{ route('reward-categories.destroy', ['reward_category' => $rewardCategory->id]) }}" method="POST">  
                                @csrf 
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>
                            </form>
                            <a href="{{ route('reward-categories.show', ['reward_category' => $rewardCategory->id]) }}" class='btn btn-info btn-sm'><i class="fa fa-eye"></i> </a>
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