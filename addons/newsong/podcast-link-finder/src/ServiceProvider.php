<?php

namespace NewSong\PodcastLinkFinder;

use Statamic\Providers\AddonServiceProvider;
use NewSong\PodcastLinkFinder\Fieldtypes\PodcastLinkFinder;
use NewSong\PodcastLinkFinder\Console\Commands\TestYouTubeCommand;
use NewSong\PodcastLinkFinder\Console\Commands\BulkUpdateLinksCommand;
use NewSong\PodcastLinkFinder\Console\Commands\AutoUpdateLinksCommand;
use Illuminate\Console\Scheduling\Schedule;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        PodcastLinkFinder::class,
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $commands = [
        TestYouTubeCommand::class,
        BulkUpdateLinksCommand::class,
        AutoUpdateLinksCommand::class,
    ];

    protected $vite = [
        'input' => [
            'resources/js/addon.js',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function bootAddon()
    {
        // Config is automatically loaded from config/ directory

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
        });
    }
}
