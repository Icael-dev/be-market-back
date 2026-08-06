<?php

return [

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1,::1'),
        env('.SANCTUM_STATEFUL_DOMAINS', '')
    ))),

    'guard' => ['web'],

    'middleware' => [],

];
