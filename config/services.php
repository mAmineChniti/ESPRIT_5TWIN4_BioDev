<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Generative AI
    |--------------------------------------------------------------------------
    |
    | Drives the greenwashing detector and the product assistant. Set AI_KEY to
    | enable it. Groq and OpenRouter both publish free tiers that need no card,
    | so a key is all that stands between this app and a working AI.
    |
    | Every provider below except Gemini speaks the OpenAI chat-completions
    | shape, so switching is a matter of changing provider/base_url/model.
    |
    */

    'ai' => [
        'key' => env('AI_KEY'),
        'provider' => env('AI_PROVIDER', 'groq'),
        'model' => env('AI_MODEL', 'openai/gpt-oss-120b'),
        // Optional override. When unset, the endpoint is resolved from the
        // provider map below, so changing AI_PROVIDER cannot leave the request
        // pointed at the previous provider.
        'base_url' => env('AI_BASE_URL'),
        'timeout' => (int) env('AI_TIMEOUT', 25),

        // Free tiers, no payment method required.
        'endpoints' => [
            'groq' => 'https://api.groq.com/openai/v1',
            'openrouter' => 'https://openrouter.ai/api/v1',
            'openai' => 'https://api.openai.com/v1',
            'together' => 'https://api.together.xyz/v1',
        ],

        // Gemini uses its own request shape rather than the OpenAI one, so it
        // has its own model setting. AI_GEMINI_MODEL wins; AI_MODEL is only
        // honoured when it does not look like an OpenAI-style name (those
        // contain a "/"), so switching providers without changing models
        // cannot send a Groq name to the Gemini endpoint.
        'gemini_base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'gemini_model' => env('AI_GEMINI_MODEL', str_contains((string) env('AI_MODEL', ''), '/') ? 'gemini-2.5-flash' : env('AI_MODEL', 'gemini-2.5-flash')),
    ],

];
