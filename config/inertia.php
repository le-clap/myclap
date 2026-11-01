<?php

// Only overrides; everything else uses inertia-laravel's defaults.
return [
    'pages' => [
        'ensure_pages_exist' => false,
        'paths' => [resource_path('js/Pages')],
        'extensions' => ['vue'],
    ],
];
