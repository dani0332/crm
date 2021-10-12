<x-action :editRoute="route('agediscount.edit', ['agediscount' => $row->id])"
    :deleteRoute="route('agediscount.destroy', ['agediscount' => $row->id])"
    :viewRoute="route('agediscount.show', ['agediscount' => $row->id])" permission="discount-management" />
