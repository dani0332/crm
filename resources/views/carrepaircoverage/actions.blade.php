<x-action :editRoute="route('carrepaircoverage.edit', ['carrepaircoverage' => $row->id])"
    :deleteRoute="route('carrepaircoverage.destroy', ['carrepaircoverage' => $row->id])"
    :viewRoute="route('carrepaircoverage.show', ['carrepaircoverage' => $row->id])" permission="car-repair-coverage"/>