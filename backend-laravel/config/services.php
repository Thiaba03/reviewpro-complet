<?php

return [

    

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_places' => [
        'key' => env('GOOGLE_PLACES_API_KEY'),
    ],
    'huggingface' => [
        'key' => env('HUGGINGFACE_API_KEY'),
        'sentiment_model' => env('HUGGINGFACE_SENTIMENT_MODEL', 'cmarkea/distilcamembert-base-sentiment'),
    ],
    'reviewpro_ai' => [
    'url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8001'),
    'timeout' => (int) env('AI_SERVICE_TIMEOUT', 10),
],

'reviewpro_rgpd' => [
    'review_retention_days' => (int) env('REVIEW_RETENTION_DAYS', 730),
],
];
