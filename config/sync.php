<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MaPOS Synchronization Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for synchronizing data between the Desktop app and
    | the central Web-hosted server.
    |
    */

    // Central Server URL (where the web application is hosted)
    'server_url' => env('MAPOS_SYNC_SERVER_URL', 'http://127.0.0.1:8080'),

    // Secret Token for authenticating sync requests
    'api_token' => env('MAPOS_SYNC_API_TOKEN', 'mapos-local-sync-secret-token'),

    // Branch Information
    'branch_id' => env('MAPOS_BRANCH_ID', 'branch-utama'),
    'branch_name' => env('MAPOS_BRANCH_NAME', 'Cabang Utama'),

    // Request Timeout in seconds
    'timeout' => (int) env('MAPOS_SYNC_TIMEOUT', 15),
];
