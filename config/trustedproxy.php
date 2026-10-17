<?php

use Illuminate\Http\Request;

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | The addresses of the proxies in front of this application, so that
    | X-Forwarded-For is believed rather than ignored.
    |
    | The origin map places the visitor from the request address, so behind
    | nginx, Cloudflare or any load balancer this matters more than usual: with
    | nothing trusted here, every visitor is located as the load balancer, and
    | the map confidently draws them all in the wrong place.
    |
    | Empty by default, which trusts nothing and leaves X-Forwarded-For alone.
    | "*" trusts any proxy, which also means trusting a header a visitor can set
    | for themselves: that would let anyone place themselves wherever they like,
    | so name the real proxy addresses here and use "*" only on a network where
    | nothing untrusted sits in front of it.
    |
    | Read by Illuminate\Http\Middleware\TrustProxies, which is the Laravel
    | middleware's own configuration key.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxy Headers
    |--------------------------------------------------------------------------
    |
    | The headers believed to describe the original request when it came via a
    | trusted proxy. The default is every header Laravel recognises, which is
    | what an nginx or Cloudflare front end sends.
    |
    */

    'headers' => Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO
        | Request::HEADER_X_FORWARDED_AWS_ELB,

];
