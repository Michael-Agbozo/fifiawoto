<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Virus scanning for beneficiary documents
    |--------------------------------------------------------------------------
    |
    | "none" stores files without scanning. "clamav" scans each file with a
    | running clamd before it is stored, and refuses the upload if a virus is
    | found or the scanner can't be reached.
    |
    */

    'scanner' => env('UPLOAD_SCANNER', 'none'),

    // e.g. unix:///var/run/clamav/clamd.ctl or tcp://127.0.0.1:3310
    'clamav_socket' => env('CLAMAV_SOCKET', 'unix:///var/run/clamav/clamd.ctl'),
];
