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
        'issuer' => env('CLA_ISSUER'),
        'client_id' => env('CLA_CLIENT_ID'),
        'client_secret' => env('CLA_CLIENT_SECRET'),
        'redirect' => env('CLA_REDIRECT_URI'),
    ],

];
