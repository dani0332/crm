<div style="with:100%;text-align: center;">
        @can('healthqoutes-list')
        <div style='display: inline-block;'>
                <a href="{{ route('healthqoutes.show', ['healthqoute' => $row->id])}}"  class='btn btn-info btn-sm'><i class='fa fa-eye'></i></a>        
        </div>
        @endcan
</div>