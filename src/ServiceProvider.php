<?php

namespace NewSong\PodcastLinkFinder;

use Statamic\Providers\AddonServiceProvider;
use NewSong\PodcastLinkFinder\Fieldtypes\PodcastLinkFinder;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        PodcastLinkFinder::class,
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
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
