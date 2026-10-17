<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Visitor location
    |--------------------------------------------------------------------------
    |
    | The origin map draws a line from where the visitor is to where the food
    | came from. The origin half is always knowable from the product record.
    | The visitor half is resolved in two steps, most accurate first:
    |
    |   1. The browser's own geolocation, when the visitor presses the button
    |      on the map. Precise, but client-side and always optional.
    |   2. The IP lookup below, resolved on the server so the distance is real
    |      content that survives without JavaScript.
    |
    | Step 2 sends the visitor's IP address to a third party. Switching
    | GEOIP_ENABLED off keeps the visitor's IP on the server; the map then
    | falls back to the browser button and, if that is declined too, says it
    | could not work out where the visitor is rather than inventing a figure.
    |
    */

    'ip' => [
        'enabled' => env('GEOIP_ENABLED', true),

        // A free service that needs no key and no card. Only the request IP is
        // sent, and the answer is cached per IP so the same visitor costs one
        // lookup per day rather than one per page view.
        'endpoint' => env('GEOIP_ENDPOINT', 'https://ipwho.is'),

        // Short on purpose: this sits in front of a page render, so a slow or
        // unreachable provider must cost the visitor a coarse map, not a wait.
        'timeout' => (int) env('GEOIP_TIMEOUT', 3),

        'cache_ttl' => (int) env('GEOIP_CACHE_TTL', 86400),
    ],

];
