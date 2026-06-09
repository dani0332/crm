<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

class PreventRequestForgery extends Middleware
{
    /**
     * The URIs that should be excluded from verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/resource/store',
        '/form/*',
    ];
}
