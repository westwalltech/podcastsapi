<?php

use Illuminate\Support\Facades\Route;
use NewSong\PodcastLinkFinder\Http\Controllers\PodcastSearchController;
use NewSong\PodcastLinkFinder\Http\Controllers\YouTubeLivestreamController;

Route::prefix('podcast-link-finder')
    ->name('podcast-link-finder.')
    ->middleware(['can:access podcast link finder', 'throttle:60,1'])
    ->group(function () {
        Route::get('/search-episodes', [PodcastSearchController::class, 'searchEpisodes'])
            ->name('search');

        Route::post('/find-links', [PodcastSearchController::class, 'findLinks'])
            ->name('find-links');

        // Stricter rate limit for platform searches (calls external APIs)
        Route::post('/search-platforms', [PodcastSearchController::class, 'searchPlatforms'])
            ->middleware('throttle:20,1')
            ->name('search-platforms');

        // YouTube Livestream endpoints
        Route::post('/youtube-livestream/{entryId}', [YouTubeLivestreamController::class, 'fetch'])
            ->middleware('throttle:10,1')
            ->name('youtube-livestream.fetch');

        Route::get('/youtube-livestream/upcoming', [YouTubeLivestreamController::class, 'upcoming'])
            ->name('youtube-livestream.upcoming');

        Route::post('/youtube-livestream/validate', [YouTubeLivestreamController::class, 'validateUrl'])
            ->name('youtube-livestream.validate');
    });
