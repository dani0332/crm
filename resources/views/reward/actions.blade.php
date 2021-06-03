<x-action :editRoute="route('reward.edit', ['reward' => $row->id])"
    :deleteRoute="route('reward.destroy', ['reward' => $row->id])"
    :viewRoute="route('reward.destroy', ['reward' => $row->id])" permission="rewards"/>
