<x-action :editRoute="route('tmcallstatus.edit', ['tmcallstatus' => $row->id])"
    :deleteRoute="route('tmcallstatus.destroy', ['tmcallstatus' => $row->id])"
    :viewRoute="route('tmcallstatus.show', ['tmcallstatus' => $row->id])" permission="tmcallstatus-status"/>