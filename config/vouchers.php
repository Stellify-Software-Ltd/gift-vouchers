<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Operator key
    |--------------------------------------------------------------------------
    |
    | A single shared key that opens the desk. Small businesses share a till,
    | so "who did it" is recorded per transaction as a staff name instead of
    | per-user accounts. Set OPERATOR_KEY in .env; sign-in is refused while
    | it is empty.
    |
    */

    'operator_key' => env('OPERATOR_KEY', ''),

];
