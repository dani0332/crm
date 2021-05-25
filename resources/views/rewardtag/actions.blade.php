<div class="row">
    @can('reward-tags-edit')
            <div class="col-md-1">
                    <a href="{{ route('reward-tags.edit', ['reward_tag' => $row->id])}}"  class='no-style-btn'><i class='fa fa-edit'></i> 
                    </a>
            </div> 
    @endcan
    @can('reward-tags-delete')
    <div class="col-md-2">
            <form action="{{ route('reward-tags.destroy', ['reward_tag' => $row->id])}}" method='POST' style="margin-top: -3px;">  
                    @csrf 
                    @method('DELETE')
                    <button type='submit' class='no-style-btn'><i class='fa fa-trash'></i></button>
            </form>
    </div>
    @endcan
    @can('reward-tags-list')
    <div class="col-md-1">
            <a href="{{ route('reward-tags.show', ['reward_tag' => $row->id])}}"  class='no-style-btn'><i class='fa fa-eye'></i> </a>
    </div>      
    @endcan
</div>
