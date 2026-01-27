<?php

use Illuminate\Support\Facades\Config;
use NewSong\PodcastLinkFinder\Services\YouTubeService;

describe('isSearchAllowedToday', function () {
    it('allows search when no days are configured', function () {
        Config::set('podcast-link-finder.youtube.search_days', []);

        $service = new YouTubeService;
        expect($service->isSearchAllowedToday())->toBeTrue();
    });

    it('allows search on configured days', function () {
        $today = now()->format('l'); // e.g., 'Sunday'
        Config::set('podcast-link-finder.youtube.search_days', [$today]);

        $service = new YouTubeService;
        expect($service->isSearchAllowedToday())->toBeTrue();
    });

    it('blocks search on non-configured days', function () {
        // Get a day that is not today
        $notToday = now()->addDay()->format('l');
        Config::set('podcast-link-finder.youtube.search_days', [$notToday]);

        $service = new YouTubeService;
        expect($service->isSearchAllowedToday())->toBeFalse();
    });
});

describe('getSearchRestrictionMessage', function () {
    it('returns null when search is allowed', function () {
        Config::set('podcast-link-finder.youtube.search_days', []);

        $service = new YouTubeService;
        expect($service->getSearchRestrictionMessage())->toBeNull();
    });

    it('returns message for single day restriction', function () {
        $notToday = now()->addDay()->format('l');
        Config::set('podcast-link-finder.youtube.search_days', [$notToday]);

        $service = new YouTubeService;
        $message = $service->getSearchRestrictionMessage();

        expect($message)->toContain($notToday);
        expect($message)->toContain('only available on');
    });

    it('returns message for multiple day restrictions', function () {
        // Get two days that don't include today
        $day1 = now()->addDays(1)->format('l');
        $day2 = now()->addDays(2)->format('l');
        Config::set('podcast-link-finder.youtube.search_days', [$day1, $day2]);

        $service = new YouTubeService;
        $message = $service->getSearchRestrictionMessage();

        expect($message)->toContain($day1);
        expect($message)->toContain($day2);
    });
});
