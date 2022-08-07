<?php

return [
    'key_path' => storage_path('keys/service-account.json'),
    'project_id' => env('DOCUMENT_PROJECT_ID', ''),
    'processor_id' => env('DOCUMENT_PROCESSOR_ID', ''),
    'location' => env('DOCUMENT_LOCATION', 'us'),
];
