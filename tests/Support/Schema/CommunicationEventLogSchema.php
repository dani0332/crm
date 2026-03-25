<?php

namespace Tests\Support\Schema;

use Illuminate\Database\Schema\Blueprint;

class CommunicationEventLogSchema
{
    public function register(): void
    {
        SchemaUtils::ensureTables([
            'communication_event_log' => function (Blueprint $table) {
                $table->id();
                $table->string('quote_uuid');
                $table->unsignedInteger('quote_type_id');
                $table->string('event_channel');
                $table->string('communication_type');
                $table->string('action_event')->nullable();
                $table->timestamps();
            },
        ]);
    }
}
