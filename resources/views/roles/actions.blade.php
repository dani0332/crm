<div class="row">
        @can('role-edit')
        <div class="col-md-1">
                <a href="{{ route('roles.edit', ['role' => $row->id])}}" style="margin-top: -55px;" class='no-style-btn'><i class='fa fa-edit'></i> </a>
        </div>     
        @endcan

        @can('role-delete')
        <div class="col-md-1">
                <form action="{{ route('roles.destroy', ['role' => $row->id])}}" method='POST'>  
                        @csrf 
                        @method('DELETE')
                        <button type='submit' class='no-style-btn'><i class='fa fa-trash'></i></button>
                </form>
        </div>
        @endcan

        @can('role-list')
        <div class="col-md-1">
                <a href="{{ route('roles.show', ['role' => $row->id])}}" style="margin-top: -55px;" class='no-style-btn'><i class='fa fa-eye'></i> 
                </a>
        </div>
        @endcan
</div>