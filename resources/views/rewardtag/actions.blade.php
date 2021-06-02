<x-action :editRoute="route('reward-tags.edit', ['reward_tag' => $row->id])"
    :deleteRoute="route('reward-tags.destroy', ['reward_tag' => $row->id])"
    :viewRoute="route('reward-tags.destroy', ['reward_tag' => $row->id])" permission="reward-tags"/>
