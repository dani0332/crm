<?php

declare(strict_types=1);

use App\Support\StatusChangeRequestNotes;
use Illuminate\Http\Request;

it('uses background payload for synthetic CLI request', function (): void {
    $request = Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => 'Symfony']);

    $decoded = json_decode(StatusChangeRequestNotes::toJson($request), true);

    expect($decoded)
        ->toHaveKey('source', 'background')
        ->toHaveKey('argv');
});

it('uses HTTP-style payload when path is not root in console', function (): void {
    $request = Request::create(
        '/send-update/store',
        'POST',
        [],
        [],
        [],
        ['HTTP_USER_AGENT' => 'Mozilla/5.0', 'REMOTE_ADDR' => '192.0.2.1'],
    );

    $decoded = json_decode(StatusChangeRequestNotes::toJson($request), true);

    expect($decoded)
        ->toMatchArray([
            'method' => 'POST',
            'path' => 'send-update/store',
            'ip' => '192.0.2.1',
            'user_agent' => 'Mozilla/5.0',
        ]);
});
