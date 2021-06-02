<x-action :editRoute="route('status.edit', ['status' => $row->id])"
    :deleteRoute="route('status.destroy', ['status' => $row->id])"
    :viewRoute="route('status.show', ['status' => $row->id])" permission="status"/>
