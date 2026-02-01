<?php

return [
    'NNTB' => [
        'sfera_server' => env('DB_SUBIEKT_NNTB_HOST'),
        'sfera_database' => env('DB_SUBIEKT_NNTB_DATABASE'),
        'sfera_db_user' => env('DB_SUBIEKT_NNTB_USERNAME'),
        'sfera_db_password' => env('DB_SUBIEKT_NNTB_PASSWORD'),
        'sfera_operator' => env('SFERA_NNTB_USER'),
        'sfera_operator_password' => env('SFERA_NNTB_PASSWORD'), ],
    'PE' => [
        'sfera_server' => env('DB_SUBIEKT_PE_HOST'),
        'sfera_database' => env('DB_SUBIEKT_PE_DATABASE'),
        'sfera_db_user' => env('DB_SUBIEKT_PE_USERNAME'),
        'sfera_db_password' => env('DB_SUBIEKT_PE_PASSWORD'),
        'sfera_operator' => env('SFERA_PE_USER'),
        'sfera_operator_password' => env('SFERA_PE_PASSWORD'), ],

];
