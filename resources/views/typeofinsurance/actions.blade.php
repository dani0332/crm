<div class="row">
    @can('type-of-insurance-edit')
            <div class="col-md-1">
                    <a href="{{ route('typeofinsurance.edit', ['typeofinsurance' => $row->id])}}"  class='no-style-btn'><i class='fa fa-edit'></i> 
                    </a>
            </div> 
    @endcan
    @can('type-of-insurance-delete')
    <div class="col-md-2">
            <form action="{{ route('typeofinsurance.destroy', ['typeofinsurance' => $row->id])}}" method='POST' style="margin-top: -3px;">  
                    @csrf 
                    @method('DELETE')
                    <button type='submit' class='no-style-btn'><i class='fa fa-trash'></i></button>
            </form>
    </div>
    @endcan
    @can('type-of-insurance-list')
    <div class="col-md-1">
            <a href="{{ route('typeofinsurance.show', ['typeofinsurance' => $row->id])}}"  class='no-style-btn'><i class='fa fa-eye'></i> </a>
    </div>      
    @endcan
</div>
