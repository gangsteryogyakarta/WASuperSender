<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WAHA Server Configuration
    |--------------------------------------------------------------------------
    */
    
    'base_url' => env('WAHA_BASE_URL', 'http://localhost:3000'),
    'api_key' => env('WAHA_API_KEY', ''),
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    | Configure rate limits to prevent WhatsApp blocking
    */
    
    'rate_limits' => [
        'messages_per_minute' => env('WAHA_RATE_LIMIT_PER_MINUTE', 30),
        'messages_per_hour' => env('WAHA_RATE_LIMIT_PER_HOUR', 500),
        'delay_between_messages' => env('WAHA_MESSAGE_DELAY', 2), // seconds
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    */
    
    'retry' => [
        'max_attempts' => 3,
        'backoff_seconds' => [5, 30, 120],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */
    
    'webhook' => [
        'secret' => env('WAHA_WEBHOOK_SECRET', ''),
        'events' => [
            'message',
            'message.ack',
            'session.status',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Session
    |--------------------------------------------------------------------------
    */
    
    'default_session' => env('WAHA_DEFAULT_SESSION', 'default'),
];
