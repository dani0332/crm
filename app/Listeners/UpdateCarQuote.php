<?php

namespace App\Listeners;

class UpdateCarQuote
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $model = $event->model;
        $model->updated_at = $event->updatedAt;
        $model->updated_by = $event->updatedBy;
        $model->save();
    }
}
