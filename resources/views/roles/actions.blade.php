<div style="with:100%;text-align: center;">
        @can('role-edit')
        <div style='display: inline-block;'>
                <a href="{{ route('roles.edit', ['role' => $row->id])}}" style="margin-top: -55px;" class='btn btn-info btn-sm'><i class='fa fa-edit'></i> </a>
        </div>     
        @endcan

        @can('role-delete')
        <div style='display: inline-block;'>
                <form action="{{ route('roles.destroy', ['role' => $row->id])}}" method='POST'>  
                        @csrf 
                        @method('DELETE')
                        <button type='submit' class='btn btn-danger btn-sm'><i class='fa fa-trash'></i></button>
                </form>
        </div>
        @endcan

        @can('role-list')
        <div style='display: inline-block;'>
                <a href="{{ route('roles.show', ['role' => $row->id])}}" style="margin-top: -55px;" class='btn btn-info btn-sm'><i class='fa fa-eye'></i> 
                </a>
        </div>
        @endcan
</div>