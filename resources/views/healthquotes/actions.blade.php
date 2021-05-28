<div style="with:100%;text-align: center;">
        @can('healthquotes-list')
        <div style='display: inline-block;'>
                <a href="{{ route('healthquotes.show', ['healthquote' => $row->id])}}"  class='no-style-btn'><i class='fa fa-eye'></i></a>
        </div>
        @endcan
</div>
