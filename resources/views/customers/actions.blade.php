<div style="with:100%;text-align: center;">
        @can('customers-list')
        <div style='display: inline-block;'>
                <a href="{{ route('customer.edit', ['customer' => $row->id])}}"  class='no-style-btn'><i class='fa fa-edit'></i></a>        
        </div>
        @endcan
        @can('customers-edit')
        <div style='display: inline-block;'>
                <a href="{{ route('customer.show', ['customer' => $row->id])}}"  class='no-style-btn'><i class='fa fa-eye'></i></a>        
        </div>
        @endcan
</div>