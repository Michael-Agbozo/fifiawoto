<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Read by Laravel's TrustProxies middleware. Only these proxies may set the
    | X-Forwarded-* headers (client IP, scheme, host). Comma-separated IPs or
    | CIDRs, or "*" to trust the immediate caller — use "*" only when the app
    | is reachable solely through a proxy (Coolify/Traefik). Unset trusts no
    | one, so clients cannot spoof their IP. Laravel Cloud is auto-detected.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
