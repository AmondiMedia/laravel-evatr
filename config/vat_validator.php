<?php

return [
    'evatr_api_url' => 'https://evatr.bff-online.de/',

    /*
    |--------------------------------------------------------------------------
    | Requester VAT ID (UstId_1)
    |--------------------------------------------------------------------------
    |
    | Your own German VAT identification number (Umsatzsteuer-Identifikationsnummer).
    | This is required by the eVatR API for requests.
    | Example: 'DE123456789'
    |
    */
    'requester_vat_id' => env('EVATR_REQUESTER_VAT_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Request timeout in seconds.
    |
    */
    'timeout' => 10,
];
