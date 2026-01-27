<?php

use NewSong\PodcastLinkFinder\Services\YouTubeLivestreamService;

describe('extractVideoId', function () {
    it('extracts video ID from standard YouTube URL', function () {
        $service = new YouTubeLivestreamService();
        $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        expect($service->extractVideoId($url))->toBe('dQw4w9WgXcQ');
    });

    it('extracts video ID from YouTube live URL', function () {
        $service = new YouTubeLivestreamService();
        $url = 'https://www.youtube.com/live/dQw4w9WgXcQ';
        expect($service->extractVideoId($url))->toBe('dQw4w9WgXcQ');
    });

    it('extracts video ID from short youtu.be URL', function () {
        $service = new YouTubeLivestreamService();
        $url = 'https://youtu.be/dQw4w9WgXcQ';
        expect($service->extractVideoId($url))->toBe('dQw4w9WgXcQ');
    });

    it('extracts video ID from embed URL', function () {
        $service = new YouTubeLivestreamService();
        $url = 'https://www.youtube.com/embed/dQw4w9WgXcQ';
        expect($service->extractVideoId($url))->toBe('dQw4w9WgXcQ');
    });

    it('returns null for invalid URL', function () {
        $service = new YouTubeLivestreamService();
        $url = 'https://example.com/not-youtube';
        expect($service->extractVideoId($url))->toBeNull();
    });

    it('returns null for empty URL', function () {
        $service = new YouTubeLivestreamService();
        expect($service->extractVideoId(''))->toBeNull();
    });

    it('handles URLs with additional parameters', function () {
        $service = new YouTubeLivestreamService();
        $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30s';
        expect($service->extractVideoId($url))->toBe('dQw4w9WgXcQ');
    });
});

describe('isConfigured', function () {
    it('returns true when API key and channel ID are set', function () {
        $service = new YouTubeLivestreamService();
        expect($service->isConfigured())->toBeTrue();
    });

    it('returns false when channel ID is empty', function () {
        // Override just the channel ID to empty
        config([
            'youtube-livestream.channel_id' => '',
            'podcast-link-finder.youtube.channel_id' => '',
        ]);

        $service = new YouTubeLivestreamService();
        expect($service->isConfigured())->toBeFalse();
    });
});
