<div class="row">
    @can('claims-status-edit')
            <div class="col-md-1">
                    <a href="{{ route('claimsstatus.edit', ['claimsstatus' => $row->id])}}"  class='no-style-btn'><i class='fa fa-edit'></i> 
                    </a>
            </div> 
    @endcan
    @can('claims-status-delete')
    <div class="col-md-2">
            <form action="{{ route('claimsstatus.destroy', ['claimsstatus' => $row->id])}}" method='POST' style="margin-top: -3px;">  
                    @csrf 
                    @method('DELETE')
                    <button type='submit' class='no-style-btn'><i class='fa fa-trash'></i></button>
            </form>
    </div>
    @endcan
    @can('claims-status-list')
    <div class="col-md-1">
            <a href="{{ route('claimsstatus.show', ['claimsstatus' => $row->id])}}"  class='no-style-btn'><i class='fa fa-eye'></i> </a>
    </div>      
    @endcan
</div>
