<div style="with:100%;text-align: center;">
        @can('carqoutes-list')
        <div style='display: inline-block;'>
                <a href="{{ route('carqoutes.show', ['carqoute' => $row->id])}}"  class='btn btn-info btn-sm'><i class='fa fa-eye'></i></a>        
        </div>
        @endcan
</div>