<div class="row">
        @can('partners-edit')
                <div class="col-md-1">
                        <a href="{{ route('partner.edit', ['partner' => $row->id])}}"  class='no-style-btn'><i class='fa fa-edit'></i> 
                        </a>
                </div> 
        @endcan
        @can('partners-delete')
        <div class="col-md-1">
                <form action="{{ route('partner.destroy', ['partner' => $row->id])}}" method='POST' style="margin-top: -3px;">  
                        @csrf 
                        @method('DELETE')
                        <button type='submit' class='no-style-btn'><i class='fa fa-trash'></i></button>
                </form>
        </div>
        @endcan
        @can('partners-list')
        <div class="col-md-1">
                <a href="{{ route('partner.show', ['partner' => $row->id])}}"  class='no-style-btn'><i class='fa fa-eye'></i> </a>
        </div>      
        @endcan
</div>
