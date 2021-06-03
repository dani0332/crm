<x-action :editRoute="route('transaction.edit', ['transaction' => $row->id])"
    :deleteRoute="route('transaction.destroy', ['transaction' => $row->id])"
    :viewRoute="route('transaction.show', ['transaction' => $row->id])" permission="transaction"/>
