<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local real-data snapshot
    |--------------------------------------------------------------------------
    |
    | This file is intentionally stored outside version control. Copy the
    | supplied MySQL export to this path (or override it with the environment
    | variable) before running `php artisan db:seed` on a fresh database.
    |
    */
    'sql_path' => env(
        'HALINH_TRAVEL_IMPORT_SQL_PATH',
        database_path('seeders/local/halinh_travel.sql'),
    ),
];
