<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo content on the live site
    |--------------------------------------------------------------------------
    |
    | Lets the sample events and sponsorship records run in production so people
    | can preview them, and shows a notice on public pages saying they are samples.
    | Turn it off and run `php artisan demo:remove` before the real launch.
    |
    */

    'live_content' => (bool) env('DEMO_CONTENT', false),

];
