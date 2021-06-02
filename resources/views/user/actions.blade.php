<x-action :editRoute="route('users.edit', ['user' => $row->id])"
    :deleteRoute="route('users.destroy', ['user' => $row->id])"
    :viewRoute="route('users.destroy', ['user' => $row->id])" permission="users"/>
