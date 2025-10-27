<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Transistor API Configuration
    |--------------------------------------------------------------------------
    */
    'transistor' => [
        'api_key' => env('TRANSISTOR_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Spotify API Configuration
    |--------------------------------------------------------------------------
    */
    'spotify' => [
        'client_id' => env('SPOTIFY_CLIENT_ID'),
        'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
        'show_id' => env('SPOTIFY_SHOW_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | YouTube API Configuration
    |--------------------------------------------------------------------------
    */
    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY'),
        'channel_id' => env('YOUTUBE_CHANNEL_ID', ''),

        // Days of the week when YouTube search is allowed (to conserve API quota)
        // Valid values: 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'
        // Set to empty array [] to allow searches every day
        'search_days' => ['Sunday'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Apple Podcasts Configuration
    |--------------------------------------------------------------------------
    */
    'apple_podcasts' => [
        'show_id' => env('APPLE_PODCASTS_SHOW_ID', '1039720149'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    */
    'search' => [
        'fuzzy_threshold' => 0.6, // 0-1, higher = stricter matching
        'date_range_days' => 14, // How many days before/after to search
        'max_results' => 20, // Max episodes to show in dropdown
    ],
];
