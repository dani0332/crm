<x-action :editRoute="route('reward-categories.edit', ['reward_category' => $row->id])"
    :deleteRoute="route('reward-categories.destroy', ['reward_category' => $row->id])"
    :viewRoute="route('reward-categories.destroy', ['reward_category' => $row->id])" permission="reward-categories"/>
