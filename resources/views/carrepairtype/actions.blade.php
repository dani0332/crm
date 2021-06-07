<x-action :editRoute="route('carrepairtype.edit', ['carrepairtype' => $row->id])"
    :deleteRoute="route('carrepairtype.destroy', ['carrepairtype' => $row->id])"
    :viewRoute="route('carrepairtype.show', ['carrepairtype' => $row->id])" permission="car-repair-type"/>