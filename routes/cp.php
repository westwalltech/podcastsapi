<?php

use Illuminate\Support\Facades\Route;
use NewSong\PodcastLinkFinder\Http\Controllers\PodcastSearchController;
use NewSong\PodcastLinkFinder\Http\Controllers\YouTubeLivestreamController;

Route::prefix('podcast-link-finder')->name('podcast-link-finder.')->group(function () {
    Route::get('/search-episodes', [PodcastSearchController::class, 'searchEpisodes'])
        ->name('search');

    Route::post('/find-links', [PodcastSearchController::class, 'findLinks'])
        ->name('find-links');

    Route::post('/search-platforms', [PodcastSearchController::class, 'searchPlatforms'])
        ->name('search-platforms');

    // YouTube Livestream endpoints
    Route::post('/youtube-livestream/{entryId}', [YouTubeLivestreamController::class, 'fetch'])
        ->name('youtube-livestream.fetch');

    Route::get('/youtube-livestream/upcoming', [YouTubeLivestreamController::class, 'upcoming'])
        ->name('youtube-livestream.upcoming');

    Route::post('/youtube-livestream/validate', [YouTubeLivestreamController::class, 'validate'])
        ->name('youtube-livestream.validate');
});
