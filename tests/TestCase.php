<?php

namespace NewSong\PodcastLinkFinder\Tests;

use NewSong\PodcastLinkFinder\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up default config values for testing
        config([
            'podcast-link-finder.transistor.api_key' => 'test-transistor-key',
            'podcast-link-finder.spotify.client_id' => 'test-spotify-id',
            'podcast-link-finder.spotify.client_secret' => 'test-spotify-secret',
            'podcast-link-finder.spotify.show_id' => 'test-show-id',
            'podcast-link-finder.youtube.api_key' => 'test-youtube-key',
            'podcast-link-finder.youtube.channel_id' => 'test-channel-id',
            'podcast-link-finder.youtube.search_days' => [],
            'podcast-link-finder.apple_podcasts.show_id' => 'test-apple-id',
            'youtube-livestream.api_key' => 'test-youtube-key',
            'youtube-livestream.channel_id' => 'test-channel-id',
            'youtube-livestream.enabled' => true,
        ]);
    }
}
