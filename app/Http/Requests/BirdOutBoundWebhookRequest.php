<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class BirdOutBoundWebhookRequest extends FormRequest
{
    public function rules()
    {
        return [
            'id' => 'required',
            'channelId' => 'required',
            'sender.connector.id' => 'required|uuid',
            'sender.connector.identifierValue' => 'required',
            'sender.connector.annotations.name' => 'required|string',
            'body.type' => 'required|string',
            'reference' => 'required|string',
            'parts' => 'required|array',
            'parts.*.platformReference' => 'required|string',
            'status' => 'required|string',
            'reason' => 'required|string',
            'direction' => 'required|string',
            'template.projectId' => 'required|uuid',
            'template.version' => 'required|string',
            'template.locale' => 'required|string',
            'template.variables.default' => 'required|string',
            'template.variables.additionalProp1' => 'required|string',
            'template.variables.additionalProp2' => 'required|string',
            'template.variables.additionalProp3' => 'required|string',
            'lastStatusAt' => 'required',
            'createdAt' => 'required',
            'updatedAt' => 'required',
            'details' => 'required|string',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
