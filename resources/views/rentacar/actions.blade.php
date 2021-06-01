<div class="row">
    @can('rent-a-car-edit')
            <div class="col-md-1">
                    <a href="{{ route('rentacar.edit', ['rentacar' => $row->id])}}"  class='no-style-btn'><i class='fa fa-edit'></i> 
                    </a>
            </div> 
    @endcan
    @can('rent-a-car-delete')
    <div class="col-md-2">
            <form action="{{ route('rentacar.destroy', ['rentacar' => $row->id])}}" method='POST' style="margin-top: -3px;">  
                    @csrf 
                    @method('DELETE')
                    <button type='submit' class='no-style-btn'><i class='fa fa-trash'></i></button>
            </form>
    </div>
    @endcan
    @can('rent-a-car-list')
    <div class="col-md-1">
            <a href="{{ route('rentacar.show', ['rentacar' => $row->id])}}"  class='no-style-btn'><i class='fa fa-eye'></i> </a>
    </div>      
    @endcan
</div>
