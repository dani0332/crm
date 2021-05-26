<div class="row">
    @can('reward-categories-edit')
            <div class="col-md-1">
                    <a href="{{ route('reward-categories.edit', ['reward_category' => $row->id])}}"  class='no-style-btn'><i class='fa fa-edit'></i> 
                    </a>
            </div> 
    @endcan
    @can('reward-categories-delete')
    <div class="col-md-2">
            <form action="{{ route('reward-categories.destroy', ['reward_category' => $row->id])}}" method='POST' style="margin-top: -3px;">  
                    @csrf 
                    @method('DELETE')
                    <button type='submit' class='no-style-btn'><i class='fa fa-trash'></i></button>
            </form>
    </div>
    @endcan
    @can('reward-categories-list')
    <div class="col-md-1">
            <a href="{{ route('reward-categories.show', ['reward_category' => $row->id])}}"  class='no-style-btn'><i class='fa fa-eye'></i> </a>
    </div>      
    @endcan
</div>
