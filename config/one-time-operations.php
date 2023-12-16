<?php

use TimoKoerber\LaravelOneTimeOperations\Jobs\OneTimeOperationProcessJob;

return [

    // Directory name - the directory in which your operation files are being saved (based on root directory)
    'directory' => 'operations',

    // Table name - name of the table that stores your operation entries
    'table' => 'operations',

    // The job that will execute each operation
    'job' => OneTimeOperationProcessJob::class,
];
