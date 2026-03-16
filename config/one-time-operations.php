<?php

return [

    // Directory name - the directory in which your operation files are being saved (based on root directory)
    'directory' => 'operations',

    // Table name - name of the table that stores your operation entries
    'table' => 'operations',

    // Database Connection Name - Change the model connection, support for Multitenancy
    // Only change when you want to deviate from your system default repository
    'connection' => null,

    // Queue Connection Name
    // Only change when you want to use a specific queue connection different from default
    'queue_connection' => env('ONE_TIME_OPERATIONS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
];
