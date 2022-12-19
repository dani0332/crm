<?php

namespace App\Listeners;

class JobListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the job processing event.
     *
     * @param  object  $event
     * @return void
     */
    public function handleJobProcessing($event)
    {
        info('The job '.json_decode($event->job->getRawBody())->displayName.' - '.now()->toDateTimeString().' is about to be processed.');
    }

    /**
     * Handle the job processed event.
     *
     * @param  object  $event
     * @return void
     */
    public function handleJobProcessed($event)
    {
        info('The job '.json_decode($event->job->getRawBody())->displayName.' - '.now()->toDateTimeString().' has been successfully processed.');
    }
}
