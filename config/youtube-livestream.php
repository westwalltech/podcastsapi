<?php

return [
    /*
    |--------------------------------------------------------------------------
    | YouTube Livestream Auto-Fetch
    |--------------------------------------------------------------------------
    |
    | Configuration for automatically fetching YouTube livestream URLs
    | for scheduled Sunday services.
    |
    */

    'enabled' => env('YOUTUBE_LIVESTREAM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | YouTube API Configuration
    |--------------------------------------------------------------------------
    |
    | API credentials for accessing YouTube Data API v3.
    | Falls back to podcast-link-finder.youtube settings if not set.
    |
    */

    'api_key' => env('YOUTUBE_API_KEY'),

    // Channel handle (e.g., @newsongchurchokc) or channel ID (UC...)
    'channel_handle' => env('YOUTUBE_CHANNEL_HANDLE', '@newsongchurchokc'),

    // Direct channel ID (preferred over handle for performance)
    'channel_id' => env('YOUTUBE_CHANNEL_ID'),

    /*
    |--------------------------------------------------------------------------
    | Schedule Configuration
    |--------------------------------------------------------------------------
    |
    | When to run the auto-fetch command. Default is Sundays at 8:00 AM
    | and 9:45 AM Central Time to catch scheduled Sunday services.
    |
    */

    'schedule' => [
        // Times to run on Sundays (24-hour format)
        'times' => ['08:00', '09:45'],

        // Timezone for schedule and date matching
        'timezone' => 'America/Chicago',
    ],

    /*
    |--------------------------------------------------------------------------
    | Collection & Field Configuration
    |--------------------------------------------------------------------------
    |
    | Specifies which Statamic collection and fields to update.
    |
    */

    'collection' => env('YOUTUBE_LIVESTREAM_COLLECTION', 'messages'),

    // The date field on entries to match against livestream dates
    'date_field' => 'air_date',

    // The URL field to update with the livestream link
    'url_field' => 'youtube_url',

    // Field to track when the URL was last fetched
    'fetched_at_field' => 'youtube_fetched_at',

    /*
    |--------------------------------------------------------------------------
    | Matching Configuration
    |--------------------------------------------------------------------------
    |
    | How to match entries to livestreams.
    |
    */

    'matching' => [
        // Only match livestreams on Sundays (set to false to allow any day)
        'sunday_only' => false,

        // If multiple livestreams on same date, pick earliest
        'prefer_earliest' => true,

        // Days to look ahead for upcoming entries
        'days_ahead' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Overwrite Behavior
    |--------------------------------------------------------------------------
    |
    | When to update the youtube_url field.
    |
    */

    'overwrite' => [
        // Always check if existing URL is still valid
        'validate_existing' => true,

        // Replace invalid URLs automatically
        'replace_invalid' => true,

        // Never overwrite valid existing URLs
        'preserve_valid' => true,
    ],
];
