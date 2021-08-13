<x-action :editRoute="route('vehicledepreciation.edit', ['vehicledepreciation' => $row->id])"
    :deleteRoute="route('vehicledepreciation.destroy', ['vehicledepreciation' => $row->id])"
    :viewRoute="route('vehicledepreciation.show', ['vehicledepreciation' => $row->id])" permission="vehicle-depreciation"/>
