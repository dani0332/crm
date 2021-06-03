<x-action :editRoute="route('rentacar.edit', ['rentacar' => $row->id])"
    :deleteRoute="route('rentacar.destroy', ['rentacar' => $row->id])"
    :viewRoute="route('rentacar.show', ['rentacar' => $row->id])" permission="rent-a-car"/>