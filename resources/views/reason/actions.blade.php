<x-action :editRoute="route('reason.edit', ['reason' => $row->id])"
    :deleteRoute="route('reason.destroy', ['reason' => $row->id])"
    :viewRoute="route('reason.show', ['reason' => $row->id])" permission="reason"/>
