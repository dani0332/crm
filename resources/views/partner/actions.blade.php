<div style="with:100%;text-align: center;">
<div style='display: inline-block;'>
        <a href="{{ route('partner.edit', ['partner' => $row->id])}}" style="margin-top: -55px;" class='btn btn-info btn-sm'><i class='fa fa-edit'></i> 
        </a>
</div>

<div style='display: inline-block;'>
        <form action="{{ route('partner.destroy', ['partner' => $row->id])}}" method='POST'>  
                @csrf 
                @method('DELETE')
                <button type='submit' class='btn btn-danger btn-sm'><i class='fa fa-trash'></i></button>
        </form>
</div>
<div style='display: inline-block;'>
        <a href="{{ route('partner.show', ['partner' => $row->id])}}" style="margin-top: -55px;" class='btn btn-info btn-sm'><i class='fa fa-eye'></i> 
        </a>
</div>