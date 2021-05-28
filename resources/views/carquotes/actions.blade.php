<div style="with:100%;text-align: center;">
        @can('carquotes-list')
        <div style='display: inline-block;'>
                <a href="{{ route('carquotes.show', ['carquote' => $row->id])}}"  class='no-style-btn'><i class='fa fa-eye'></i></a>
        </div>
        @endcan
</div>
