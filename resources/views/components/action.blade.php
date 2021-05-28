<div class='action-container'>
    @can($permission.'-edit')
    <div class='action-item'>
        <a href="{{ $editRoute}}"  class='no-style-btn'><i class='fa fa-edit'></i></a>
    </div>
    @endcan
    @can($permission.'-delete')
    <div class='action-item'>
    <form action="{{ $deleteRoute }}" method='POST' style="margin-top: -3px;">
        @csrf
        @method('DELETE')
        <button type='submit' class='no-style-btn'><i class='fa fa-trash'></i></button>
    </form>
    </div>
    @endcan
    @can($permission.'-list')
    <div class='action-item'>
    <a href="{{ $viewRoute }}"  class='no-style-btn'><i class='fa fa-eye'></i></a>
    </div>
    @endcan
</div>
