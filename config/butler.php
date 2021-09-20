<?php

return [

    'audit' => [

        'url' => env('BUTLER_AUDIT_URL'),

        'token' => env('BUTLER_AUDIT_TOKEN'),

        'driver' => env('BUTLER_AUDIT_DRIVER'),

        'default_initiator_resolver' => true,

        'extend_bus_dispatcher' => true,

    ],

];
