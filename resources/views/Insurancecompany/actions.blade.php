<x-action :editRoute="route('insurancecompany.edit', ['insurancecompany' => $row->id])"
    :deleteRoute="route('insurancecompany.destroy', ['insurancecompany' => $row->id])"
    :viewRoute="route('insurancecompany.show', ['insurancecompany' => $row->id])" permission="insurance-company"/>
