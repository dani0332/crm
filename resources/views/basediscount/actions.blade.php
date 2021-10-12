<x-action :editRoute="route('discountenginebase.edit', ['discountenginebase' => $row->id])"
    :deleteRoute="route('discountenginebase.destroy', ['discountenginebase' => $row->id])"
    :viewRoute="route('discountenginebase.show', ['discountenginebase' => $row->id])" permission="discount-management"/>
