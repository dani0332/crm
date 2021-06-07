<div class='action-container'>
    @can($permission.'-list')
    <div class='action-item'>
    <a href="{{ $viewRoute }}"  class='no-style-btn'><i class='fa fa-eye'></i></a>
    </div>
    @endcan
    @can($permission.'-edit')
    <div class='action-item'>
        <a href="{{ $editRoute}}"  class='no-style-btn'><i class='fa fa-edit'></i></a>
    </div>
    @endcan
    @can($permission.'-delete')
    <div class='action-item'>
        <a href="#" date-route="{{$deleteRoute}}"   class='btn btn-warning btn-sm delete'>Delete</a>
    {{-- <form action="{{ $deleteRoute }}" method='POST' style="margin-top: -2px;">
        @csrf
        @method('DELETE')
        <button type='submit' class='no-style-btn' style='margin-left:-7px;color:#5A738E;'><i class='fa fa-trash'></i></button>
    </form> --}}
    </div>
    @endcan
</div>
