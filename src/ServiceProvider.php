<?php

namespace NewSong\PodcastLinkFinder;

use Statamic\Providers\AddonServiceProvider;
use NewSong\PodcastLinkFinder\Fieldtypes\PodcastLinkFinder;
use NewSong\PodcastLinkFinder\Console\Commands\TestYouTubeCommand;
use NewSong\PodcastLinkFinder\Console\Commands\BulkUpdateLinksCommand;

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
    }
}
