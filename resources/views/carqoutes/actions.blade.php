<div style="with:100%;text-align: center;">
        @can('users-edit')
        <div style='display: inline-block;'>
                <a href="{{ route('users.edit', ['user' => $row->id])}}" style="margin-top: -55px;" class='btn btn-info btn-sm'><i class='fa fa-edit'></i> 
                </a>
        </div>
        @endcan
<div style='display: inline-block;'>
        @can('users-delete')
        <form action="{{ route('users.destroy', ['user' => $row->id])}}" method='POST'>  
                @csrf 
                @method('DELETE')
                <button type='submit' class='btn btn-danger btn-sm'><i class='fa fa-trash'></i></button>
        </form>
        @endcan
</div>
        @can('users-list')
        <div style='display: inline-block;'>
                
                        <a href="{{ route('users.show', ['user' => $row->id])}}" style="margin-top: -55px;" class='btn btn-info btn-sm'><i class='fa fa-eye'></i></a>
                
        </div>
        @endcan
</div>