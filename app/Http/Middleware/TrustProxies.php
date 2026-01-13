<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array|string|null
     */
    protected $proxies = [
        '127.0.0.1',        // Localhost
        '172.16.0.0/12',    // Docker default bridge network
        '10.0.0.0/8',       // Private network range
        '192.168.0.0/16',   // Private network range
        '172.19.0.0/16',    // UAT Server
        '172.27.0.0/16',    // Test Server
        '172.20.0.0/16',    // Staging Server 1
        '172.22.0.0/16',    // Staging Server 2
        '172.18.0.0/16',    // PROD Server
    ];

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_AWS_ELB;
}
