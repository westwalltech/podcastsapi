<?php

namespace NewSong\PodcastLinkFinder;

use Statamic\Providers\AddonServiceProvider;
use Statamic\Facades\GraphQL;
use NewSong\PodcastLinkFinder\Fieldtypes\PodcastLinkFinder;
use NewSong\PodcastLinkFinder\Fieldtypes\YouTubeLivestream;
use NewSong\PodcastLinkFinder\Console\Commands\TestYouTubeCommand;
use NewSong\PodcastLinkFinder\Console\Commands\BulkUpdateLinksCommand;
use NewSong\PodcastLinkFinder\Console\Commands\AutoUpdateLinksCommand;
use NewSong\PodcastLinkFinder\Console\Commands\FetchYouTubeLivestreamsCommand;
use NewSong\PodcastLinkFinder\GraphQL\PodcastLinksType;
use NewSong\PodcastLinkFinder\GraphQL\PlatformLinkType;
use Illuminate\Console\Scheduling\Schedule;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        PodcastLinkFinder::class,
        YouTubeLivestream::class,
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $commands = [
        TestYouTubeCommand::class,
        BulkUpdateLinksCommand::class,
        AutoUpdateLinksCommand::class,
        FetchYouTubeLivestreamsCommand::class,
    ];

    protected $vite = [
        'input' => [
            'resources/js/addon.js',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function register()
    {
        parent::register();

        // Merge YouTube livestream config
        $this->mergeConfigFrom(
            __DIR__.'/../config/youtube-livestream.php',
            'youtube-livestream'
        );
    }

    public function bootAddon()
    {
        // Config is automatically loaded from config/ directory

        // Register GraphQL types
        GraphQL::addType(PlatformLinkType::class);
        GraphQL::addType(PodcastLinksType::class);

        // Publish fieldsets
        $this->publishes([
            __DIR__.'/../resources/fieldsets' => resource_path('fieldsets/vendor/podcast-link-finder'),
        ], 'podcast-link-finder-fieldsets');

        // Publish YouTube livestream config
        $this->publishes([
            __DIR__.'/../config/youtube-livestream.php' => config_path('youtube-livestream.php'),
        ], 'podcast-link-finder-youtube-livestream-config');

        // Schedule auto-update task
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            if (config('podcast-link-finder.auto_update.enabled')) {
                $day = config('podcast-link-finder.auto_update.schedule.day', 'tuesdays');
                $time = config('podcast-link-finder.auto_update.schedule.time', '08:00');

                $schedule->command('podcast:auto-update')
                    ->weekly()
                    ->{$day}()
                    ->at($time)
                    ->withoutOverlapping()
                    ->runInBackground();
            }

            // Schedule YouTube livestream fetch (Sundays at configured times)
            if (config('youtube-livestream.enabled', true)) {
                $timezone = config('youtube-livestream.schedule.timezone', 'America/Chicago');
                $times = config('youtube-livestream.schedule.times', ['08:00', '09:45']);

                foreach ($times as $time) {
                    $schedule->command('newsong:fetch-youtube-livestreams')
                        ->weekly()
                        ->sundays()
                        ->at($time)
                        ->timezone($timezone)
                        ->withoutOverlapping()
                        ->runInBackground();
                }
            }
        });
    }
}
