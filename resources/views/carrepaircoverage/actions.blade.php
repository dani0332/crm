<div class="row">
    @can('car-repair-coverage-edit')
            <div class="col-md-1">
                    <a href="{{ route('carrepaircoverage.edit', ['carrepaircoverage' => $row->id])}}"  class='no-style-btn'><i class='fa fa-edit'></i> 
                    </a>
            </div> 
    @endcan
    @can('car-repair-coverage-delete')
    <div class="col-md-2">
            <form action="{{ route('carrepaircoverage.destroy', ['carrepaircoverage' => $row->id])}}" method='POST' style="margin-top: -3px;">  
                    @csrf 
                    @method('DELETE')
                    <button type='submit' class='no-style-btn'><i class='fa fa-trash'></i></button>
            </form>
    </div>
    @endcan
    @can('car-repair-coverage-list')
    <div class="col-md-1">
            <a href="{{ route('carrepaircoverage.show', ['carrepaircoverage' => $row->id])}}"  class='no-style-btn'><i class='fa fa-eye'></i> </a>
    </div>      
    @endcan
</div>
