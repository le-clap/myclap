<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as CLA authentication and other external APIs.
    |
    */

    'cla' => [
        'host' => env('CLA_AUTH_HOST', 'https://centralelilleassos.fr'),
        'identifier' => env('CLA_AUTH_IDENTIFIER', 'myclap'),
    ],

];
