<?php

return [
    'base_path' => app_path('Modules'),
    'namespace' => 'App\\Modules',
    'aliases' => [
        'auth' => 'Auth',
        'md' => 'MasterData',
        'disp' => 'Dispatch',
        'ctr' => 'Contract',
        'fin' => 'Finance',
        'rental' => 'Rental',
        'other' => 'Other',
        'dp' => 'DriverPayroll'
    ],
    'api_prefix' => 'api',
    'stubs_path' => base_path('stubs/modules'),

    'postman' => [
        'auth_middleware' => [
            'auth',
            'auth:*'
        ],
        'auth_type' => 'bearer',
        'token_variable' => 'access_token',
        'base_url_variable' => 'base_url'
    ]
];
