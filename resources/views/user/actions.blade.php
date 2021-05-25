<div class="row">
        @can('users-edit')
        <div class="col-md-1">
                <a href="{{ route('users.edit', ['user' => $row->id])}}" style="margin-top: -55px;" class='no-style-btn'><i class='fa fa-edit'></i> 
                </a>
        </div>
        @endcan
        <div class="col-md-1">
        @can('users-delete')
        <form action="{{ route('users.destroy', ['user' => $row->id])}}" method='POST'>  
                @csrf 
                @method('DELETE')
                <button type='submit' class='no-style-btn'><i class='fa fa-trash'></i></button>
        </form>
        @endcan
        </div>
        @can('users-list')
        <div class="col-md-1">
                <a href="{{ route('users.show', ['user' => $row->id])}}" style="margin-top: -55px;" class='no-style-btn'><i class='fa fa-eye'></i></a>        
        </div>
        @endcan
</div>