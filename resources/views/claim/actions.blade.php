<div class="row">
        @can('claim-edit')
        <div class="col-md-1">
        <a href="{{ route('claims.edit', ['claim' => $row->id])}}"  class='no-style-btn'> <i class='fa fa-edit'> </i></a>
        </div> 
        @endcan
        @can('claim-delete')
        <div class="col-md-1">
        <form action="{{ route('claims.destroy', ['claim' => $row->id])}}" method='POST' style="margin-top: -3px;">  
        @csrf 
        @method('DELETE')
        <button type='submit' class='no-style-btn'> <i class='fa fa-trash'> </i></button>
        </form>
        </div>
        @endcan
        @can('claim-list')
        <div class="col-md-1">
        <a href="{{ route('claims.show', ['claim' => $row->id])}}" class='no-style-btn'> <i class='fa fa-eye'> </i></a>
        </div>      
        @endcan
</div>
