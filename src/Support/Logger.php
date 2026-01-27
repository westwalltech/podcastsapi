<?php

namespace NewSong\PodcastLinkFinder\Support;

use Illuminate\Support\Facades\Log;

class Logger
{
    protected static function channel(): string
    {
        return 'podcast-link-finder';
    }

    public static function info(string $message, array $context = []): void
    {
        if (config('podcast-link-finder.logging.enabled', true)) {
            Log::channel(static::channel())->info($message, $context);
        }
    }

    public static function warning(string $message, array $context = []): void
    {
        if (config('podcast-link-finder.logging.enabled', true)) {
            Log::channel(static::channel())->warning($message, $context);
        }
    }

    public static function error(string $message, array $context = []): void
    {
        if (config('podcast-link-finder.logging.enabled', true)) {
            Log::channel(static::channel())->error($message, $context);
        }
    }

    public static function debug(string $message, array $context = []): void
    {
        if (config('podcast-link-finder.logging.enabled', true)) {
            Log::channel(static::channel())->debug($message, $context);
        }
    }
}
