
<x-action :editRoute="route('aml.edit', ['aml' => $row->id])"
    :deleteRoute="route('aml.destroy', ['aml' => $row->id])"
    :viewRoute="route('aml.destroy', ['aml' => $row->id])" permission="aml"/>
