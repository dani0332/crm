<div class="row">
    @can('rewards-edit')
        <div class="col-md-1">
         <a href="{{ route('reward.edit', ['reward' => $row->id])}}"  class='no-style-btn'><i class='fa fa-edit'></i></a>
        </div> 
    @endcan
    @can('rewards-delete')
    <div class="col-md-2">
            <form action="{{ route('reward.destroy', ['reward' => $row->id])}}" method='POST' style="margin-top: -3px;">  
                    @csrf 
                    @method('DELETE')
                    <button type='submit' class='no-style-btn'><i class='fa fa-trash'></i></button>
            </form>
    </div>
    @endcan
    @can('rewards-list')
    <div class="col-md-1">
            <a href="{{ route('reward.show', ['reward' => $row->id])}}"  class='no-style-btn'><i class='fa fa-eye'></i> </a>
    </div>      
    @endcan
</div>
