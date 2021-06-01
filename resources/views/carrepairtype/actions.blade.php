<div class="row">
    @can('car-repair-type-edit')
            <div class="col-md-1">
                    <a href="{{ route('carrepairtype.edit', ['carrepairtype' => $row->id])}}"  class='no-style-btn'><i class='fa fa-edit'></i> 
                    </a>
            </div> 
    @endcan
    @can('car-repair-type-delete')
    <div class="col-md-2">
            <form action="{{ route('carrepairtype.destroy', ['carrepairtype' => $row->id])}}" method='POST' style="margin-top: -3px;">  
                    @csrf 
                    @method('DELETE')
                    <button type='submit' class='no-style-btn'><i class='fa fa-trash'></i></button>
            </form>
    </div>
    @endcan
    @can('car-repair-type-list')
    <div class="col-md-1">
            <a href="{{ route('carrepairtype.show', ['carrepairtype' => $row->id])}}"  class='no-style-btn'><i class='fa fa-eye'></i> </a>
    </div>      
    @endcan
</div>
