<x-action :editRoute="route('transection.edit', ['transection' => $row->id])"
    :deleteRoute="route('transection.destroy', ['transection' => $row->id])"
    :viewRoute="route('transection.show', ['transection' => $row->id])" permission="transection"/>
