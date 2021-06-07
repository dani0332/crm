<x-action :editRoute="route('subtypeofinsurance.edit', ['subtypeofinsurance' => $row->id])"
    :deleteRoute="route('subtypeofinsurance.destroy', ['subtypeofinsurance' => $row->id])"
    :viewRoute="route('subtypeofinsurance.show', ['subtypeofinsurance' => $row->id])" permission="sub-type-of-insurance"/>