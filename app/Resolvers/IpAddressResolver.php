<?php

declare(strict_types=1);

namespace App\Resolvers;

use Illuminate\Support\Facades\Request;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Resolver;

class IpAddressResolver implements Resolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve(Auditable $auditable)
    {
        $request = Request::instance();

        // Check for forwarded IP headers in priority order
        // These headers are set by nginx in your docker/nginx.conf

        // 1. Check X-Real-IP (most reliable for single proxy)
        if ($realIp = $request->header('X-Real-IP')) {
            if (filter_var($realIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $realIp;
            }
        }

        // 2. Check X-Forwarded-For (may contain multiple IPs)
        if ($forwardedFor = $request->header('X-Forwarded-For')) {
            // X-Forwarded-For can contain multiple IPs: "client, proxy1, proxy2"
            $ips = array_map('trim', explode(',', $forwardedFor));

            // Return the first public IP in the chain
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // 3. Fallback to Laravel's request->ip() which respects TrustProxies
        return $request->ip();
    }
}
