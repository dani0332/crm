<x-action :editRoute="route('typeofinsurance.edit', ['typeofinsurance' => $row->id])"
    :deleteRoute="route('typeofinsurance.destroy', ['typeofinsurance' => $row->id])"
    :viewRoute="route('typeofinsurance.show', ['typeofinsurance' => $row->id])" permission="type-of-insurance"/>
