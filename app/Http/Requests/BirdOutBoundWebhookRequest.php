<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class BirdOutBoundWebhookRequest extends FormRequest
{
    public function rules()
    {
        return [
            'payload.id' => 'required',
            'payload.channelId' => 'required',
            'payload.sender.connector.id' => 'required',
            'payload.sender.connector.identifierValue' => 'required',
            'payload.receiver.contacts' => 'required|array',
            'payload.receiver.contacts.*.id' => 'required|uuid',
            'payload.receiver.contacts.*.identifierKey' => 'required',
            'payload.receiver.contacts.*.identifierValue' => 'required|email',
            'payload.receiver.contacts.*.annotations.name' => 'required|string',
            'payload.reference' => 'nullable|string',
            'payload.direction' => 'required',
            'payload.status' => 'required',
            'payload.reason' => 'nullable|string',
            'payload.messageTags' => 'required|array',
            'payload.messageTags.*' => 'required|string',
            'payload.lastStatusAt' => 'required',
            'payload.createdAt' => 'required',
            'payload.updatedAt' => 'required',
            'payload.batchId' => 'nullable',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
