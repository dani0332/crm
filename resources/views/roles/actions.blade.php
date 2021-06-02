<x-action :editRoute="route('roles.edit', ['role' => $row->id])"
    :deleteRoute="route('roles.destroy', ['role' => $row->id])"
    :viewRoute="route('roles.destroy', ['role' => $row->id])" permission="role"/>
