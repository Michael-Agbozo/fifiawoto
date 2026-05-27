<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin notification email
    |--------------------------------------------------------------------------
    |
    | Mailbox that receives operational alerts: new beneficiary applications,
    | new volunteer applications, recorded donations, contact-form messages.
    |
    */

    'admin_email' => env('ADMIN_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@fifiawoto.org')),
];
