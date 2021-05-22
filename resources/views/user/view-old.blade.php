@extends('layouts.app')
@section('title','View Partner')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Partners</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('rewards/partner/create') }}" class="btn btn-success btn-sm">Create Partner</a></li>
                </ul>
                <div class="clearfix"></div>
                
            </div>
            <div class="x_content">
                <br />
                <table id="datatable" class="table table-striped table-bordered" style="width:100%">
                      <thead>
                        <tr>
                          <th>Name</th>
                          <th>Name Ar</th>
                          <th>Logo</th>
                          <th>Is Active</th>
                          <th>Created At</th>
                          <th>Updated At</th>
                          <th>Actions</th>
                        </tr>
                      </thead>


                      <tbody>
                        
                        @foreach($partners as $key => $partner)
                        <tr>
                          <td>{{ $partner->name }}</td>
                          <td>{{ $partner->name_ar }}</td>
                          <td><img src="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$partner->logo_image }}" style='width:40px;' /></td>
                          <td>{{ $partner->is_active }}</td>
                          <td>{{ $partner->created_at }}</td>
                          <td>{{ $partner->updated_at }}</td>
                          <td><a href="{{ route('partner.edit', ['partner' => $partner->id]) }}" class='btn btn-info btn-sm'><i class="fa fa-edit"></i> </a>
                          <form action="{{ route('partner.destroy', ['partner' => $partner->id]) }}" method="POST">  
                                @csrf 
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>
                            </form>
                            <a href="{{ route('partner.show', ['partner' => $partner->id]) }}" class='btn btn-info btn-sm'><i class="fa fa-eye"></i> </a>
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